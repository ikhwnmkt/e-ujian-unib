<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_requires_a_valid_email_otp(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->once()->andReturn('student@example.com');
        $googleUser->shouldReceive('getName')->once()->andReturn('Student');

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.otp'))
            ->assertSessionHas('google_otp');
        $this->assertGuest();

        $user = User::where('email', 'student@example.com')->firstOrFail();
        $sentMessage = Mail::mailer()->getSymfonyTransport()->messages()->last();
        $this->assertMatchesRegularExpression('/adalah:\s*(\d{6})/', $sentMessage->toString());
        preg_match('/adalah:\s*(\d{6})/', $sentMessage->toString(), $matches);

        $this->post(route('google.otp.verify'), ['code' => $matches[1]])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_an_incorrect_google_otp_does_not_authenticate_the_user(): void
    {
        $user = User::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password',
            'role' => 'mahasiswa',
        ]);

        $this->withSession([
            'google_otp' => [
                'user_id' => $user->id,
                'code_hash' => bcrypt('123456'),
                'expires_at' => now()->addMinutes(10)->timestamp,
                'attempts' => 0,
            ],
        ])->post(route('google.otp.verify'), ['code' => '000000'])
            ->assertRedirect()
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_five_incorrect_attempts_end_the_google_login_challenge(): void
    {
        $user = User::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password',
            'role' => 'mahasiswa',
        ]);

        $this->withSession([
            'google_otp' => [
                'user_id' => $user->id,
                'code_hash' => bcrypt('123456'),
                'expires_at' => now()->addMinutes(10)->timestamp,
                'attempts' => 0,
            ],
        ]);

        for ($attempt = 1; $attempt < 5; $attempt++) {
            $this->post(route('google.otp.verify'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('google.otp.verify'), ['code' => '000000'])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('google_otp');
        $this->assertGuest();
    }
}
