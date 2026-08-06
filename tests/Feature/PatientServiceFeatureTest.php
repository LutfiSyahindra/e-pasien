<?php

namespace Tests\Feature;

use App\Events\PatientServiceConversationUpdated;
use App\Events\PatientServiceMessageSent;
use App\Events\PatientServiceReceiptUpdated;
use App\Models\PatientServiceConversation;
use App\Models\PatientServiceMessage;
use App\Models\User;
use App\Notifications\PatientServiceMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PASIEN_SERVICE',
            'EPASIEN.MENU.PASIEN_SERVICE.KELOLA',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_patient_can_open_service_page_and_start_a_conversation(): void
    {
        $patient = $this->patient();
        Event::fake([PatientServiceMessageSent::class]);

        $this->actingAs($patient)
            ->get(route('patientService.index'))
            ->assertOk()
            ->assertSeeText('Pasien Service')
            ->assertSeeText('Mulai percakapan baru')
            ->assertSee('data-service-workspace', false)
            ->assertSee('data-patient-service-sidebar-badge', false)
            ->assertSee('ep-premium-navbar', false)
            ->assertDontSee('search-toggle-icon', false)
            ->assertDontSee('apps-name', false)
            ->assertDontSee('perfect-scrollbar/css/perfect-scrollbar.css', false)
            ->assertDontSee('perfect-scrollbar/js/perfect-scrollbar.js', false);

        $response = $this->actingAs($patient)->postJson(
            route('patientService.conversations.store'),
            [
                'category' => 'complaint',
                'subject' => 'Tidak dapat memilih jadwal',
                'message' => 'Pilihan jadwal dokter tidak muncul di aplikasi.',
            ],
        );

        $response->assertCreated()
            ->assertJsonPath('conversation.patient_id', $patient->id)
            ->assertJsonPath('conversation.status', PatientServiceConversation::STATUS_WAITING_ADMIN)
            ->assertJsonPath('conversation.status_label', 'Menunggu Tim Pasien Service');

        $this->assertDatabaseHas('patient_service_conversations', [
            'patient_id' => $patient->id,
            'category' => 'complaint',
            'subject' => 'Tidak dapat memilih jadwal',
            'status' => PatientServiceConversation::STATUS_WAITING_ADMIN,
        ]);
        $this->assertDatabaseHas('patient_service_messages', [
            'sender_id' => $patient->id,
            'body' => 'Pilihan jadwal dokter tidak muncul di aplikasi.',
        ]);
        Event::assertDispatched(PatientServiceMessageSent::class);
    }

    public function test_conversation_and_messages_are_private_to_the_patient_and_admin(): void
    {
        $owner = $this->patient();
        $otherPatient = $this->patient();
        $conversation = $this->conversation($owner);

        $this->actingAs($otherPatient)
            ->getJson(route('patientService.messages.index', $conversation))
            ->assertForbidden();

        $this->actingAs($otherPatient)
            ->postJson(route('patientService.messages.store', $conversation), ['message' => 'Pesan asing'])
            ->assertForbidden();

        $this->actingAs($otherPatient)
            ->get(route('patientService.index', ['conversation' => $conversation->id]))
            ->assertNotFound();

        $this->assertDatabaseMissing('patient_service_messages', ['body' => 'Pesan asing']);
    }

    public function test_admin_can_see_inbox_reply_and_is_assigned_to_the_conversation(): void
    {
        $patient = $this->patient();
        $admin = $this->admin();
        $conversation = $this->conversation($patient);
        Event::fake([PatientServiceMessageSent::class]);

        $this->actingAs($admin)
            ->get(route('patientService.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSeeText($patient->name)
            ->assertSeeText($conversation->subject);

        $this->actingAs($admin)
            ->postJson(route('patientService.messages.store', $conversation), [
                'message' => 'Baik, kendala Anda sedang kami periksa.',
            ])
            ->assertCreated()
            ->assertJsonPath('conversation.status', PatientServiceConversation::STATUS_WAITING_PATIENT);

        $this->assertDatabaseHas('patient_service_conversations', [
            'id' => $conversation->id,
            'assigned_admin_id' => $admin->id,
            'status' => PatientServiceConversation::STATUS_WAITING_PATIENT,
        ]);
        $this->assertDatabaseHas('patient_service_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'body' => 'Baik, kendala Anda sedang kami periksa.',
        ]);
    }

    public function test_participants_can_close_reopen_and_read_a_conversation(): void
    {
        $patient = $this->patient();
        $admin = $this->admin();
        $conversation = $this->conversation($patient);
        $adminMessage = PatientServiceMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'body' => 'Apakah kendalanya sudah selesai?',
        ]);
        Event::fake([PatientServiceConversationUpdated::class, PatientServiceMessageSent::class]);

        $this->actingAs($patient)
            ->patchJson(route('patientService.read', $conversation))
            ->assertOk()
            ->assertJsonPath('updated', 1);
        $this->assertNotNull($adminMessage->refresh()->read_at);

        $this->actingAs($patient)
            ->patchJson(route('patientService.status', $conversation), ['status' => 'closed'])
            ->assertOk()
            ->assertJsonPath('conversation.status', PatientServiceConversation::STATUS_CLOSED);

        $this->actingAs($patient)
            ->postJson(route('patientService.messages.store', $conversation), ['message' => 'Pesan setelah selesai'])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->patchJson(route('patientService.status', $conversation), ['status' => 'open'])
            ->assertOk()
            ->assertJsonPath('conversation.status', PatientServiceConversation::STATUS_WAITING_PATIENT);

        $this->actingAs($patient)
            ->postJson(route('patientService.messages.store', $conversation), ['message' => 'Terima kasih, saya cek lagi.'])
            ->assertCreated();

        Event::assertDispatched(PatientServiceConversationUpdated::class);
        Event::assertDispatched(PatientServiceMessageSent::class);
    }

    public function test_admin_cannot_create_a_patient_conversation_and_unprivileged_user_cannot_open_menu(): void
    {
        $admin = $this->admin();
        $unprivileged = User::factory()->create(['status' => true]);

        $this->actingAs($admin)
            ->postJson(route('patientService.conversations.store'), [
                'category' => 'question',
                'subject' => 'Topik admin',
                'message' => 'Admin tidak boleh membuat tiket pasien.',
            ])
            ->assertForbidden();

        $this->actingAs($unprivileged)
            ->get(route('patientService.index'))
            ->assertForbidden();
    }

    public function test_new_messages_notify_the_correct_recipient_with_chat_preview_and_direct_conversation_link(): void
    {
        config([
            'webpush.vapid.public_key' => 'public-test-key',
            'webpush.vapid.private_key' => 'private-test-key',
        ]);
        $patient = $this->patient();
        $otherPatient = $this->patient();
        $admin = $this->admin();
        Event::fake([PatientServiceMessageSent::class]);
        Notification::fake();

        $patientMessage = 'Mohon bantuan untuk memverifikasi data akun saya.';
        $response = $this->actingAs($patient)->postJson(
            route('patientService.conversations.store'),
            [
                'category' => 'complaint',
                'subject' => 'Verifikasi data akun',
                'message' => $patientMessage,
            ],
        )->assertCreated();
        $conversationId = $response->json('conversation.id');

        Notification::assertSentTo(
            $admin,
            PatientServiceMessageNotification::class,
            function (PatientServiceMessageNotification $notification, array $channels) use ($admin, $conversationId, $patientMessage): bool {
                $data = $notification->toArray($admin);
                $push = $notification->toWebPush($admin, $notification)->toArray();
                $conversationUrl = route('patientService.index', ['conversation' => $conversationId], false);

                return in_array('database', $channels, true)
                    && in_array('broadcast', $channels, true)
                    && in_array(WebPushChannel::class, $channels, true)
                    && $data['kind'] === 'patient_service_message'
                    && $data['conversation_id'] === $conversationId
                    && $push['body'] === $patientMessage
                    && $push['data']['kind'] === 'patient_service_message'
                    && $push['data']['conversation_id'] === $conversationId
                    && $push['data']['url'] === $conversationUrl;
            },
        );
        Notification::assertNotSentTo($otherPatient, PatientServiceMessageNotification::class);

        $conversation = PatientServiceConversation::query()->findOrFail($conversationId);
        $adminReply = 'Data akun sudah kami periksa.';
        $this->actingAs($admin)
            ->postJson(route('patientService.messages.store', $conversation), [
                'message' => $adminReply,
            ])
            ->assertCreated();

        Notification::assertSentTo(
            $patient,
            PatientServiceMessageNotification::class,
            function (PatientServiceMessageNotification $notification) use ($patient, $conversationId, $adminReply): bool {
                $push = $notification->toWebPush($patient, $notification)->toArray();

                return $notification->toArray($patient)['conversation_id'] === $conversationId
                    && $push['title'] === 'Balasan dari Pasien Service'
                    && $push['body'] === $adminReply
                    && $push['data']['url'] === route('patientService.index', ['conversation' => $conversationId], false);
            },
        );
    }

    public function test_all_active_users_from_roles_with_admin_patient_service_access_receive_patient_messages(): void
    {
        $patient = $this->patient();
        $firstTeamRole = Role::findOrCreate('Customer Care', 'web');
        $secondTeamRole = Role::findOrCreate('Informasi Rumah Sakit', 'web');
        $outsideRole = Role::findOrCreate('Keuangan', 'web');
        $firstTeamRole->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PASIEN_SERVICE',
            'EPASIEN.MENU.PASIEN_SERVICE.KELOLA',
        ]);
        $secondTeamRole->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PASIEN_SERVICE',
            'EPASIEN.MENU.PASIEN_SERVICE.KELOLA',
        ]);

        $firstTeamMember = User::factory()->create(['status' => true]);
        $secondTeamMember = User::factory()->create(['status' => true]);
        $inactiveTeamMember = User::factory()->create(['status' => false]);
        $outsideUser = User::factory()->create(['status' => true]);
        $firstTeamMember->assignRole($firstTeamRole);
        $secondTeamMember->assignRole($secondTeamRole);
        $inactiveTeamMember->assignRole($firstTeamRole);
        $outsideUser->assignRole($outsideRole);
        Event::fake([PatientServiceMessageSent::class]);
        Notification::fake();

        $response = $this->actingAs($patient)->postJson(route('patientService.conversations.store'), [
            'category' => 'question',
            'subject' => 'Informasi layanan rumah sakit',
            'message' => 'Mohon bantuan Tim Pasien Service.',
        ])->assertCreated();
        $conversation = PatientServiceConversation::query()->findOrFail($response->json('conversation.id'));

        Notification::assertSentTo($firstTeamMember, PatientServiceMessageNotification::class);
        Notification::assertSentTo($secondTeamMember, PatientServiceMessageNotification::class);
        Notification::assertNotSentTo($inactiveTeamMember, PatientServiceMessageNotification::class);
        Notification::assertNotSentTo($outsideUser, PatientServiceMessageNotification::class);

        $this->actingAs($firstTeamMember)
            ->getJson(route('patientService.messages.index', $conversation))
            ->assertOk();
        $this->actingAs($secondTeamMember)
            ->postJson(route('patientService.messages.store', $conversation), [
                'message' => 'Tim kami siap membantu Anda.',
            ])
            ->assertCreated();
        $this->actingAs($outsideUser)
            ->getJson(route('patientService.messages.index', $conversation))
            ->assertForbidden();
    }

    public function test_navbar_messages_and_in_app_read_status_stay_synchronized(): void
    {
        $patient = $this->patient();
        $admin = $this->admin();
        $conversation = $this->conversation($patient);
        $adminMessage = PatientServiceMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'body' => 'Silakan periksa kembali akun Anda.',
        ]);
        $notification = $patient->notifications()->create([
            'id' => fake()->uuid(),
            'type' => PatientServiceMessageNotification::class,
            'data' => [
                'kind' => 'patient_service_message',
                'conversation_id' => $conversation->id,
                'message_id' => $adminMessage->id,
                'title' => 'Balasan dari Pasien Service',
                'body' => 'Silakan periksa kembali akun Anda.',
                'url' => route('patientService.index', ['conversation' => $conversation->id], false),
            ],
        ]);

        $this->actingAs($patient)
            ->getJson(route('patientService.navbar'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('conversations.0.id', $conversation->id)
            ->assertJsonPath('conversations.0.unread_count', 1)
            ->assertJsonPath('conversations.0.name', 'Tim Pasien Service');

        $this->actingAs($patient)
            ->patchJson(route('patientService.read', $conversation))
            ->assertOk()
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('notification_unread_count', 0);

        $this->assertNotNull($adminMessage->refresh()->read_at);
        $this->assertDatabaseHas('patient_service_message_reads', [
            'message_id' => $adminMessage->id,
            'user_id' => $patient->id,
        ]);
        $this->assertNotNull($notification->refresh()->read_at);
        $this->actingAs($patient)
            ->getJson(route('patientService.navbar'))
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('conversations.0.unread_count', 0);
    }

    public function test_each_admin_has_an_independent_unread_message_state(): void
    {
        $patient = $this->patient();
        $firstAdmin = $this->admin();
        $secondAdmin = $this->admin();
        $conversation = $this->conversation($patient);

        $this->actingAs($firstAdmin)
            ->getJson(route('patientService.navbar'))
            ->assertJsonPath('unread_count', 1);
        $this->actingAs($secondAdmin)
            ->getJson(route('patientService.navbar'))
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($firstAdmin)
            ->patchJson(route('patientService.read', $conversation))
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->actingAs($firstAdmin)
            ->getJson(route('patientService.navbar'))
            ->assertJsonPath('unread_count', 0);
        $this->actingAs($secondAdmin)
            ->getJson(route('patientService.navbar'))
            ->assertJsonPath('unread_count', 1);
    }

    public function test_message_receipt_progresses_from_sent_to_delivered_and_read(): void
    {
        $patient = $this->patient();
        $admin = $this->admin();
        $conversation = $this->conversation($patient);
        $patientMessage = $conversation->messages()->firstOrFail();
        Event::fake([PatientServiceReceiptUpdated::class]);

        $this->actingAs($patient)
            ->getJson(route('patientService.messages.index', $conversation))
            ->assertOk()
            ->assertJsonPath("receipt_statuses.{$patientMessage->id}", 'sent');

        $this->actingAs($admin)
            ->getJson(route('patientService.navbar'))
            ->assertOk()
            ->assertJsonPath('pending_delivery_message_ids.0', $patientMessage->id);

        $this->actingAs($admin)
            ->postJson(route('patientService.deliveries'), [
                'message_ids' => [$patientMessage->id],
            ])
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->assertDatabaseHas('patient_service_message_deliveries', [
            'message_id' => $patientMessage->id,
            'user_id' => $admin->id,
        ]);
        $this->actingAs($patient)
            ->getJson(route('patientService.messages.index', $conversation))
            ->assertJsonPath("receipt_statuses.{$patientMessage->id}", 'delivered');

        $this->actingAs($admin)
            ->patchJson(route('patientService.read', $conversation))
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->assertDatabaseHas('patient_service_message_reads', [
            'message_id' => $patientMessage->id,
            'user_id' => $admin->id,
        ]);
        $this->actingAs($patient)
            ->getJson(route('patientService.messages.index', $conversation))
            ->assertJsonPath("receipt_statuses.{$patientMessage->id}", 'read');

        Event::assertDispatched(
            PatientServiceReceiptUpdated::class,
            fn (PatientServiceReceiptUpdated $event): bool => $event->messageIds === [$patientMessage->id]
                && $event->status === 'delivered'
                && $event->actorId === $admin->id,
        );
        Event::assertDispatched(
            PatientServiceReceiptUpdated::class,
            fn (PatientServiceReceiptUpdated $event): bool => $event->messageIds === [$patientMessage->id]
                && $event->status === 'read'
                && $event->actorId === $admin->id,
        );
    }

    public function test_user_cannot_acknowledge_own_or_invisible_message_as_delivered(): void
    {
        $patient = $this->patient();
        $otherPatient = $this->patient();
        $conversation = $this->conversation($patient);
        $message = $conversation->messages()->firstOrFail();

        $this->actingAs($patient)
            ->postJson(route('patientService.deliveries'), ['message_ids' => [$message->id]])
            ->assertOk()
            ->assertJsonPath('updated', 0);

        $this->actingAs($otherPatient)
            ->postJson(route('patientService.deliveries'), ['message_ids' => [$message->id]])
            ->assertOk()
            ->assertJsonPath('updated', 0);

        $this->assertDatabaseMissing('patient_service_message_deliveries', [
            'message_id' => $message->id,
        ]);
    }

    private function patient(): User
    {
        $role = Role::findOrCreate(config('access-control.patient_role', 'Patient'), 'web');
        $role->givePermissionTo(['EPASIEN.MENU', 'EPASIEN.MENU.PASIEN_SERVICE']);
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function admin(): User
    {
        $role = Role::findOrCreate('Patient Service Admin', 'web');
        $role->givePermissionTo([
            'EPASIEN.MENU',
            'EPASIEN.MENU.PASIEN_SERVICE',
            'EPASIEN.MENU.PASIEN_SERVICE.KELOLA',
        ]);
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function conversation(User $patient): PatientServiceConversation
    {
        $conversation = PatientServiceConversation::query()->create([
            'patient_id' => $patient->id,
            'category' => 'question',
            'subject' => 'Informasi jadwal dokter',
            'status' => PatientServiceConversation::STATUS_WAITING_ADMIN,
            'last_message_at' => now(),
        ]);
        $conversation->messages()->create([
            'sender_id' => $patient->id,
            'body' => 'Saya ingin menanyakan jadwal dokter.',
        ]);

        return $conversation;
    }
}
