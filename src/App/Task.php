<?php

namespace App;

class Task
{
    public $customerData;
    public $userData;
    public const ASSETS_PATH = 'assets';
    public const CUSTOMER = 'customer';
    public const USER = 'user';
    public const CUSTOMER_FILENAME = 'customer-channel.txt';
    public const USER_FILENAME = 'user-channel.txt';
    public const PARSER_REGEX = '/silence_start:\s*([0-9.]+).*?silence_end:\s*([0-9.]+).*?silence_duration:\s*([0-9.]+)/s';
    public const CHANNELS = [self::CUSTOMER, self::USER];


    public function __construct($assets = self::ASSETS_PATH)
    {
        $this->customerData = file_get_contents($assets . '/' . self::CUSTOMER_FILENAME);
        $this->userData = file_get_contents($assets . '/' . self::USER_FILENAME);
    }


    public function getAllData()
    {
        $customerChannelData = $this->parseChannelData(self::CUSTOMER);
        $userChannelData = $this->parseChannelData(self::USER);

        $parsedResult = [];
        for ($i = 0; $i < count($customerChannelData['silenceEnd']); $i++) {
            $parsedResult = $this->calculateChannelData($customerChannelData, self::CUSTOMER, $i, $parsedResult);
            $parsedResult = $this->calculateChannelData($userChannelData, self::USER, $i, $parsedResult);
        }

        $parsedResult = $this->calculateUserTalkBasedOnLastSilence($parsedResult, $customerChannelData, $userChannelData);
        $parsedResult = $this->calculateUserTalkBasedOnTotalTalk($parsedResult);
        $parsedResult = $this->calculateClearSpeech($parsedResult);

        return $this->reArrangeOutput($parsedResult);
    }

    protected function calculateChannelData($parsedChannelData, $channel, $counter, $parsedResult)
    {

        if (isset($parsedChannelData['silenceEnd'][$counter]) && isset($parsedChannelData['silenceStart'][$counter])) {
            $parsedResult[$channel][] = [$parsedChannelData['silenceEnd'][$counter], $parsedChannelData['silenceStart'][$counter]];
            $parsedResult[$channel . '_talkDuration'] += ($parsedChannelData['silenceStart'][$counter] - $parsedChannelData['silenceEnd'][$counter]);
        }

        return $parsedResult;
    }

    protected function parseChannelData($channel = null)
    {
        if (!$channel || !in_array($channel, [self::CUSTOMER, self::USER])) {
            return [];
        }

        $data = $channel == self::CUSTOMER ? $this->customerData : $this->userData;

        preg_match_all(self::PARSER_REGEX, $data, $result);

        $silenceStart = $result[1];
        $silenceEnd = $result[2];
        $silenceDuration = $result[3];
        array_unshift($silenceEnd, 0);
        array_unshift($silenceDuration, $silenceStart[0]);

        return ['silenceEnd' => $silenceEnd, 'silenceStart' => $silenceStart, 'silenceDuration' => $silenceDuration];
    }

    protected function calculateUserTalkBasedOnLastSilence($parsedResult, $customerChannelData, $userChannelData)
    {
        $lastSilenceStart = max(end($customerChannelData['silenceStart']), end($userChannelData['silenceStart']));

        $parsedResult['user_talk_percentage'] =
            ($parsedResult[self::USER . '_talkDuration'] / ($lastSilenceStart)) * 100;
        $parsedResult['user_talk_percentage'] = number_format($parsedResult['user_talk_percentage'], 2);
        return $parsedResult;
    }

    protected function calculateUserTalkBasedOnTotalTalk($parsedResult)
    {
        $parsedResult['user_talk_percentage_based_on_talk'] =
            ($parsedResult[self::USER . '_talkDuration'] / ($parsedResult[self::USER . '_talkDuration'] + $parsedResult[self::CUSTOMER . '_talkDuration'])) * 100;

        $parsedResult['user_talk_percentage_based_on_talk'] = number_format($parsedResult['user_talk_percentage_based_on_talk'], 2);
        return $parsedResult;
    }

    protected function calculateClearSpeech($parsedResult)
    {

        $customer = $parsedResult['customer'];
        $user = $parsedResult['user'];
        $customerMonologueData = $this->accumulateClearSpeechTime($customer, $user);
        $userMonologueData = $this->accumulateClearSpeechTime($user, $customer);
        $parsedResult['longest_customer_monologue'] = $customerMonologueData[1];
        $parsedResult['total_customer_monologue'] = $customerMonologueData[0];
        $parsedResult['longest_user_monologue'] = $userMonologueData[1];
        $parsedResult['total_user_monologue'] = $userMonologueData[0];

        return $parsedResult;
    }

    private function accumulateClearSpeechTime($compare, $compareTo)
    {

        $totalMonologueTime = 0;
        $longestMonologueTime = 0;
        for ($i = 0; $i < count($compare); $i++) {
            $startSpeechCompare = $compare[$i][0];
            $endSpeechCompare = $compare[$i][1];

            $startSpeechCompareTo = $compareTo[$i][0];
            $endSpeechCompareTo = $compareTo[$i][1];
            $prevEndSpeechCompareTo = isset($compareTo[$i - 1][1]) ? $compareTo[$i - 1][1] : 0;
            if (
                (
                    $endSpeechCompare < $startSpeechCompareTo
                    &&
                    $startSpeechCompare > $prevEndSpeechCompareTo
                )
                    ||
                $startSpeechCompare > $endSpeechCompareTo
            ) {
                $newMonologueTime = ($endSpeechCompare - $startSpeechCompare);
                if ($longestMonologueTime < $newMonologueTime) {
                    $longestMonologueTime = $newMonologueTime;
                }
                $totalMonologueTime += $newMonologueTime;
            }
        }

        return [$totalMonologueTime, $longestMonologueTime];
    }

    private function reArrangeOutput($parsedResult)
    {

        $customerTalkDuration = $parsedResult['customer_talkDuration'];
        $userTalkDuration = $parsedResult['user_talkDuration'];
        unset($parsedResult['customer_talkDuration'], $parsedResult['user_talkDuration']);
        $parsedResult['customer_talkDuration'] = $customerTalkDuration;
        $parsedResult['user_talkDuration'] = $userTalkDuration;

        return $parsedResult;
    }
}