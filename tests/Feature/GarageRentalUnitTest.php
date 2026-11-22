<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\ExtensionService;
use App\Services\RentalPricingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GarageRentalUnitTest extends TestCase
{
    use RefreshDatabase;

    private RentalPricingService $pricingService;
    private AvailabilityService $availabilityService;
    private ExtensionService $extensionService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->availabilityService = new AvailabilityService();
        $this->pricingService      = new RentalPricingService($this->availabilityService);
        $this->extensionService    = new ExtensionService($this->availabilityService);
    }

    private function createUser(string $role = 'tenant', string $nik = '3578202210040001', string $email = 'tenant@test.com'): User
    {
        return User::create([
            'nik'                 => $nik,
            'name'                => ucfirst($role) . ' Test',
            'email'               => $email,
            'phone'               => '081234567890',
            'password'            => Hash::make('password'),
            'role'                => $role,
            'ktp_image_path'      => 'ktp/dummy.png',
            'verification_status' => 'approved',
        ]);
    }

    public function test_sewa_bulanan_mengikuti_jumlah_hari_kalender_dan_tarif_25x(): void
    {
        $tenant  = $this->createUser();
        $garage  = Garage::create(['code' => 'C-01', 'type' => 'C', 'daily_rate' => 25000, 'kwh_rate' => 0]);
        $vehicle = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => 'L 1234 AB',
            'brand_model' => 'Porsche Carrera GT', 'type' => 'charging',
        ]);

        // Februari 2024 (tahun kabisat = 29 hari)
        $quoteFeb = $this->pricingService->calculateQuote($garage, $vehicle, 'monthly', Carbon::parse('2024-02-15'), 1);
        $this->assertEquals(29, $quoteFeb['duration_days']);
        $this->assertEquals('2024-03-14', $quoteFeb['end_date']);
        $this->assertEquals(625000, $quoteFeb['base_rental_cost']);
        $this->assertEquals(0, $quoteFeb['charging_cost']);

        // Maret 2026 (31 hari)
        $quoteMar = $this->pricingService->calculateQuote($garage, $vehicle, 'monthly', Carbon::parse('2026-03-10'), 1);
        $this->assertEquals(31, $quoteMar['duration_days']);
        $this->assertEquals(625000, $quoteMar['base_rental_cost']);
    }

    public function test_sewa_bulanan_ditolak_jika_lebih_dari_1_bulan(): void
    {
        $tenant  = $this->createUser();
        $garage  = Garage::create(['code' => 'S-01', 'type' => 'S', 'daily_rate' => 10000, 'kwh_rate' => 0]);
        $vehicle = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => null,
            'brand_model' => 'Polygon Strattos', 'type' => 'standard',
        ]);

        $this->expectException(DomainException::class);
        $this->pricingService->calculateQuote($garage, $vehicle, 'monthly', Carbon::parse('2026-11-01'), 2);
    }

    public function test_sewa_harian_ditolak_jika_melebihi_jumlah_hari_bulan_dikurangi_1(): void
    {
        $tenant  = $this->createUser();
        $garage  = Garage::create(['code' => 'S-01', 'type' => 'S', 'daily_rate' => 10000, 'kwh_rate' => 0]);
        $vehicle = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => null,
            'brand_model' => 'Polygon Strattos', 'type' => 'standard',
        ]);

        $this->expectException(DomainException::class);
        $this->pricingService->calculateQuote($garage, $vehicle, 'daily', Carbon::parse('2026-11-01'), 30);
    }

    public function test_kompatibilitas_kendaraan_dan_garasi_serta_biaya_charging_ev(): void
    {
        $tenant  = $this->createUser();
        $garasiS = Garage::create(['code' => 'S-01', 'type' => 'S', 'daily_rate' => 10000, 'kwh_rate' => 0]);
        $garasiC = Garage::create(['code' => 'C-01', 'type' => 'C', 'daily_rate' => 25000, 'kwh_rate' => 0]);
        $garasiE = Garage::create(['code' => 'E-01', 'type' => 'E', 'daily_rate' => 50000, 'kwh_rate' => 2500]);

        $sepeda = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => null,
            'brand_model' => 'Brompton M6L', 'type' => 'standard',
        ]);
        $mobilBiasa = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => 'L 2222 CAR',
            'brand_model' => 'Honda Civic', 'type' => 'charging',
        ]);
        $ev = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => 'L 1 EV',
            'brand_model' => 'Hyundai Ioniq 5', 'type' => 'exclusive',
            'battery_capacity_kwh' => 70, 'remaining_kwh' => 20, // butuh 50 kWh
        ]);

        // 1. EV di garasi E -> kena biaya charging (50 x 2500 = 125.000)
        $quoteEvDiE = $this->pricingService->calculateQuote($garasiE, $ev, 'daily', Carbon::parse('2026-11-10'), 2);
        $this->assertEquals(100000, $quoteEvDiE['base_rental_cost']);
        $this->assertEquals(125000, $quoteEvDiE['charging_cost']);
        $this->assertEquals(225000, $quoteEvDiE['total_cost']);

        // 2. EV di garasi C -> diperbolehkan dan biaya charging 0
        $quoteEvDiC = $this->pricingService->calculateQuote($garasiC, $ev, 'daily', Carbon::parse('2026-11-10'), 2);
        $this->assertEquals(50000, $quoteEvDiC['base_rental_cost']);
        $this->assertEquals(0, $quoteEvDiC['charging_cost']);

        // 3. Sepeda hanya muncul opsi garasi S
        $opsiSepeda = $this->availabilityService->getAvailableGarages($sepeda, Carbon::parse('2026-11-10'), Carbon::parse('2026-11-12'));
        $this->assertEquals(['S-01'], $opsiSepeda->pluck('code')->all());

        // 4. Mobil biasa ditolak masuk ke garasi E
        $this->expectException(DomainException::class);
        $this->pricingService->createBooking(
            $tenant, $garasiE, $mobilBiasa, 'daily', Carbon::parse('2026-11-10'), 2, UploadedFile::fake()->image('bukti.png')
        );
    }

    public function test_plat_nomor_sepeda_boleh_kosong_dan_plat_terisi_wajib_unik(): void
    {
        $tenant1 = $this->createUser('tenant', '3578202210040001', 'tenant1@test.com');
        $tenant2 = $this->createUser('tenant', '3578202210040003', 'tenant2@test.com');

        // Dua sepeda ontel tanpa plat nomor (NULL) diperbolehkan
        $this->actingAs($tenant1)->post(route('tenant.vehicles.store'), [
            'type'          => 'standard',
            'license_plate' => '',
            'brand_model'   => 'Sepeda Ontel Gazelle',
        ])->assertSessionHasNoErrors();

        $this->actingAs($tenant2)->post(route('tenant.vehicles.store'), [
            'type'          => 'standard',
            'license_plate' => '',
            'brand_model'   => 'Polygon Monarch',
        ])->assertSessionHasNoErrors();

        // Motor dengan nomor plat terdaftar
        $this->actingAs($tenant1)->post(route('tenant.vehicles.store'), [
            'type'          => 'standard',
            'license_plate' => 'L 5555 MT',
            'brand_model'   => 'Honda Vario 160',
        ])->assertSessionHasNoErrors();

        // Duplikasi plat yang sama ditolak
        $this->actingAs($tenant2)->post(route('tenant.vehicles.store'), [
            'type'          => 'standard',
            'license_plate' => '  l  5555  mt ',
            'brand_model'   => 'Yamaha NMAX',
        ])->assertSessionHasErrors('license_plate');

        $this->assertDatabaseCount('vehicles', 3);
    }

    public function test_booking_dan_deteksi_bentrok_jadwal_serta_extend_admin(): void
    {
        $admin   = $this->createUser('admin', '3578202210040002', 'admin@test.com');
        $tenant  = $this->createUser('tenant', '3578202210040001', 'tenant@test.com');
        $garage  = Garage::create(['code' => 'C-01', 'type' => 'C', 'daily_rate' => 25000, 'kwh_rate' => 0]);
        $vehicle = Vehicle::create([
            'user_id' => $tenant->id, 'license_plate' => 'L 1234 AB',
            'brand_model' => 'Porsche Carrera GT', 'type' => 'charging',
        ]);

        $rental = $this->pricingService->createBooking(
            $tenant, $garage, $vehicle, 'daily', Carbon::parse('2026-11-21'), 5, UploadedFile::fake()->image('bukti.png')
        );

        $this->assertFalse(
            $this->availabilityService->isGarageAvailable($garage, Carbon::parse('2026-11-23'), Carbon::parse('2026-11-26'))
        );

        $extension = $this->extensionService->extendRental(
            $rental, $admin, $garage, 'daily', 5, UploadedFile::fake()->image('extend.png')
        );

        $this->assertEquals('2026-11-30', Carbon::parse($extension->new_end_date)->toDateString());
        $this->assertEquals(10, $rental->fresh()->duration_days);
        $this->assertEquals(250000, $rental->fresh()->total_cost);
    }
}