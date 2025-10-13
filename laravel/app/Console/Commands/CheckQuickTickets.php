<?php

namespace App\Console\Commands;

use App\Models\Performance;
use App\Models\Place;
use App\Services\QuickTicketsParsers\DomParser;
use App\Services\QuickTicketsParsers\PerformanceParser;
use App\Services\QuickTicketsParsers\SessionParser;
use App\Services\QuickTicketsService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
    public function handle(QuickTicketsService $quickTicketsService)
    {
        $places = Place::all();
        foreach ($places as $place) {
            try {

                $html = $quickTicketsService->getDOMbyUrl($place->url);
                $dom = DomParser::parse($html);

                $performances = PerformanceParser::getPerformances($dom);
                foreach ($performances as $performance) {
                    $name = PerformanceParser::getPerformanceName($performance);
                    $image = PerformanceParser::getPerformanceImage($performance);

                    $sessions = SessionParser::getPerformanceSessions($performance);
                    foreach ($sessions as $session) {
                        $soldOut = SessionParser::getSessionSoldOut($session);
                        try {
                            $timestamp = SessionParser::getSessionTimestamp($session);
                        } catch (Exception $e) {
                            continue;
                        }
                        $href = SessionParser::getSessionLink($session);

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
                                    'caption' => "Появился новый спектакль \"$name\""
                                        . "\n" . date('d.m.Y H:i', $timestamp)
                                        . "\n\n" . $place->name,
                                    'reply_markup' => $keyboard
                                ]);
                            }
                        } else {
                            if ($dbPerformance->sold_out == true && $soldOut == false) {
                                $dbPerformance->sold_out = $soldOut;
                                $dbPerformance->save();
                                foreach ($place->users as $user) {
                                    Telegram::sendPhoto([
                                        'chat_id' => $user->chat_id,
                                        'photo' => InputFile::create($image),
                                        'caption' => "Появились билеты на \"$name\""
                                        . "\n" . date('d.m.Y H:i', $timestamp)
                                        . "\n\n" . $place->name,
                                        'reply_markup' => $keyboard
                                    ]);
                                }
                            } else if ($dbPerformance->sold_out == false && $soldOut == true) {
                                $dbPerformance->sold_out = $soldOut;
                                $dbPerformance->save();
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
