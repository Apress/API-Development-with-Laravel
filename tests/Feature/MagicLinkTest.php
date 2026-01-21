<?php
namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that requesting a magic link creates a user, stores a magic link,
     * and queues an email.
     */
    public function test_magic_link_request_sends_email_and_creates_token()
    {
        // Fake the mail so no actual email is sent.
        Mail::fake();

        // Post a request to get a magic link.
        $response = $this->post('/auth', [
            'email' => 'test@example.com',
        ]);

        // Assert the user record was created.
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        // Assert a magic link record was created.
        $this->assertDatabaseCount('magic_links', 1);

        // Assert that the MagicLinkMail was sent to the expected recipient
        Mail::assertSent(MagicLinkMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /**
     * Test that a valid magic link allows the user to log in.
     */
    public function test_valid_magic_link_allows_login()
    {
        // Create a user using a factory or manually.
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Create a valid magic link token for the user.
        $token     = 'valid-token';
        $expiresAt = Carbon::now()->addMinutes(15);

        MagicLink::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);

        // Simulate the GET request to the magic link login route.
        $response = $this->get('/login/' . $token);

        // Assert the magic link was deleted after use.
        $this->assertDatabaseMissing('magic_links', [
            'token' => $token,
        ]);

        // Assert that the user is now authenticated.
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that an expired magic link does not allow login.
     */
    public function test_expired_magic_link_fails_login()
    {
        // Create a user.
        $user = User::factory()->create([
            'email' => 'expired@example.com',
        ]);

        // Create an expired magic link token.
        $token     = 'expired-token';
        $expiresAt = Carbon::now()->subMinutes(1);

        MagicLink::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);

        // Attempt to log in with the expired token.
        $response = $this->get('/login/' . $token);

        // Assert a 401 Unauthorized response.
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or expired link.',
            ]);

        // Ensure the expired magic link still exists in the database.
        $this->assertDatabaseHas('magic_links', [
            'token' => $token,
        ]);
    }
}
