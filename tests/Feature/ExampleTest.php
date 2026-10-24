<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_returns_a_successful_response(): void
    {
        $response = $this->get(route('login', absolute: false));

        $response->assertStatus(200);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard', absolute: false))->assertRedirect(route('login', absolute: false));
    }

    public function test_existing_password_hash_column_authenticates_users(): void
    {
        $user = User::create([
            'name' => 'CFlow User',
            'email' => 'cflow@example.test',
            'password_hash' => Hash::make('secret123'),
        ]);

        $this->post(route('login.store', absolute: false), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_register(): void
    {
        $this->post(route('register', absolute: false), [
            'name' => 'New CFlow User',
            'email' => 'new@example.test',
            'password' => 'secret123',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.test']);
    }

    public function test_user_can_create_and_feature_a_transaction(): void
    {
        $this->seed(CategorySeeder::class);
        $user = User::create([
            'name' => 'CFlow User',
            'email' => 'transactions@example.test',
            'password_hash' => Hash::make('secret123'),
        ]);
        $categoryId = DB::table('categories')->where('type', 'expense')->value('id');

        $this->actingAs($user)
            ->post(route('transactions.store', absolute: false), [
                'type' => 'expense',
                'amount' => '25000',
                'category_id' => $categoryId,
                'title' => 'Makan siang',
                'description' => 'Tes transaksi',
                'method' => 'Cash',
                'tags' => 'makan rutin',
                'trx_date' => now()->format('Y-m-d\TH:i'),
                'back' => '/',
            ])->assertRedirect('/');

        $transactionId = DB::table('transactions')->where('user_id', $user->id)->value('id');
        $this->post(route('transactions.feature', absolute: false), [
            'id' => $transactionId,
            'featured' => '1',
            'back' => '/',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'user_id' => $user->id,
            'title' => 'Makan siang',
            'featured' => 1,
        ]);
        $this->get(route('transactions.index', absolute: false))
            ->assertOk()
            ->assertSee('Makan siang');
        $this->get(route('profile.account', absolute: false))->assertOk();
    }

    public function test_authenticated_dashboard_renders_cash_trends(): void
    {
        $this->seed(CategorySeeder::class);
        $user = User::create([
            'name' => 'Dashboard User',
            'email' => 'dashboard@example.test',
            'password_hash' => Hash::make('secret123'),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertSee('Ringkasan keuangan')
            ->assertSee('Grafik pemasukan dan pengeluaran');
    }
}
