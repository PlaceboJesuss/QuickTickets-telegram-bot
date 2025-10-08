// routes/api.php
use App\Http\Controllers\TelegramWebhookController;

Route::post('/tg_webhook', [TelegramWebhookController::class, 'handle']);