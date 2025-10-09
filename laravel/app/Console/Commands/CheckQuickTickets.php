<?php

namespace App\Console\Commands;

use App\Models\Performance;
use App\Models\Place;
use App\Services\QuickTicketsParserService;
use App\Services\QuickTicketsService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use simple_html_dom\simple_html_dom;
use simple_html_dom\simple_html_dom_node;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class CheckQuickTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-quick-tickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(QuickTicketsService $quickTicketsService, QuickTicketsParserService $quickTicketsParserService)
    {
        $places = Place::all();
        foreach ($places as $place) {
            try {

                $html = $quickTicketsService->getDOMbyUrl($place->url);
                $dom = $quickTicketsParserService->parse($html);

                $performances = $quickTicketsParserService->getPerformances($dom);
                foreach ($performances as $performance) {
                    $name = $quickTicketsParserService->getPerformanceName($performance);
                    $image = $quickTicketsParserService->getPerformanceImage($performance);

                    $sessions = $quickTicketsParserService->getPerformanceSessions($performance);
                    foreach ($sessions as $session) {
                        $soldOut = $quickTicketsParserService->getSessionSoldOut($session);
                        $timestamp = $quickTicketsParserService->getSessionTimestamp($session);
                        $href = $quickTicketsParserService->getSessionLink($session);

                        $dbPerformance = Performance::findPerformance($place, $name, $timestamp);

                        $keyboard = Keyboard::make()->inline()->row([Keyboard::inlineButton(['text' => 'Купить билет', 'url' => "https://quicktickets.ru$href"])]);

                        if (!$dbPerformance) {
                            $dbPerformance = new Performance();
                            $dbPerformance->name = $name;
                            $dbPerformance->time = date('Y-m-d H:i:s', $timestamp);
                            $dbPerformance->sold_out = $soldOut;
                            $dbPerformance->save();

                            foreach ($place->users as $user) {
                                Telegram::sendPhoto([
                                    'chat_id' => $user->chat_id,
                                    'photo' => InputFile::create($image),
                                    'caption' => "Появился новый спектакль \"$name\"",
                                    'reply_markup' => $keyboard
                                ]);
                            }
                        } else {
                            if (!!$dbPerformance->sold_out != $soldOut) {
                                foreach ($place->users as $user) {
                                    Telegram::sendPhoto([
                                        'chat_id' => $user->chat_id,
                                        'photo' => InputFile::create($image),
                                        'caption' => "Появились балеты на \"$name\"",
                                        'reply_markup' => $keyboard
                                    ]);
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Log::info($e->getMessage());
            }
        }
    }
}
