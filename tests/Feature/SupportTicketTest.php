<?php

use App\Mail\NewTicketAdminMail;
use App\Models\Admin;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(TicketCategorySeeder::class);
});

test('patient can create and view a support ticket', function () {
    Mail::fake();

    $user = User::factory()->create(['profile_completed' => true]);
    $category = TicketCategory::query()->forAudience('patient')->firstOrFail();

    $this->actingAs($user);

    Livewire::test('pages::patient.support-create')
        ->set('categoryId', $category->id)
        ->set('subject', 'Payment issue')
        ->set('message', 'I was charged twice for my session.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ticket = Ticket::query()->where('creator_id', $user->id)->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->subject)->toBe('Payment issue')
        ->and($ticket->status)->toBe(Ticket::STATUS_OPEN);

    $this->get(route('patient.support.show', $ticket))
        ->assertSuccessful()
        ->assertSee('Payment issue', false)
        ->assertSee($ticket->ticket_number, false);
});

test('doctor can create a support ticket', function () {
    Mail::fake();

    $doctor = Doctor::factory()->create(['status' => 'approved', 'profile_completed' => true]);
    $category = TicketCategory::query()->forAudience('doctor')->firstOrFail();

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.settings.support-create')
        ->set('categoryId', $category->id)
        ->set('subject', 'Invoice question')
        ->set('message', 'When will my invoice be paid?')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Ticket::query()->where('creator_id', $doctor->id)->exists())->toBeTrue();
});

test('admin reply notifies ticket creator', function () {
    $user = User::factory()->create();
    $admin = Admin::factory()->create();
    $category = TicketCategory::query()->forAudience('patient')->firstOrFail();

    $ticket = app(TicketService::class)->create($user, $category->id, 'Help', 'Need assistance');

    app(TicketService::class)->replyAsAdmin($admin, $ticket, 'We are looking into this.');

    $ticket->refresh();

    expect($ticket->status)->toBe(Ticket::STATUS_ANSWERED)
        ->and($ticket->replies)->toHaveCount(1);

    expect(Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'ticket_replied')
        ->exists())->toBeTrue();
});

test('new ticket sends email to configured admin address', function () {
    Mail::fake();

    app(TicketService::class)->setNotificationEmail('tickets@example.com');

    $user = User::factory()->create();
    $category = TicketCategory::query()->forAudience('patient')->firstOrFail();

    app(TicketService::class)->create($user, $category->id, 'Bug report', 'App crashed');

    Mail::assertSent(NewTicketAdminMail::class, function (NewTicketAdminMail $mail): bool {
        return $mail->hasTo('tickets@example.com');
    });
});

test('patient cannot view another users ticket', function () {
    $owner = User::factory()->create(['profile_completed' => true]);
    $other = User::factory()->create(['profile_completed' => true]);
    $category = TicketCategory::query()->forAudience('patient')->firstOrFail();

    $ticket = app(TicketService::class)->create($owner, $category->id, 'Private', 'Secret');

    $this->actingAs($other)
        ->get(route('patient.support.show', $ticket))
        ->assertForbidden();
});
