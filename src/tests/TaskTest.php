<?php

use App\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testGetAllData()
    {

        $task = new Task('tests/assets');
        $result = $task->getAllData();
        $this->assertEquals([
            [0, 2],
            [5, 8],
        ], $result['customer']);
        $this->assertEquals([
            [0, 1],
            [3.5, 4.7],
        ], $result['user']);


        $userTalkDuration = 2.2;
        $customerTalkDuration = 5;
        $userTalkPercentage = number_format($userTalkDuration * 100 / ($userTalkDuration + $customerTalkDuration), 2);
        $this->assertEquals(3, $result['longest_customer_monologue']);
        $this->assertEquals(3, $result['total_customer_monologue']);
        $this->assertEquals(1.2, number_format($result['longest_user_monologue'], 2));
        $this->assertEquals(1.2, number_format($result['total_user_monologue'], 2));

        $this->assertEquals($userTalkPercentage, $result['user_talk_percentage_based_on_talk']);
    }

    public function testGetAllDataComplexScenario()
    {
        $task = new Task('tests/assets-complex');
        $result = $task->getAllData();
        $this->assertEquals([
            [0, 2],
            [5, 8],
            [9, 15],
            [17, 17.5],
        ], $result['customer']);
        $this->assertEquals([
            [0, 1],
            [3.5, 4.7],
            [10, 11],
            [18, 25],
        ], $result['user']);
        $userTalkDuration = 10.2;
        $customerTalkDuration = 11.5;

        $userTalkPercentage = number_format($userTalkDuration * 100 / ($userTalkDuration + $customerTalkDuration), 2);
        $this->assertEquals(3, $result['longest_customer_monologue']);
        $this->assertEquals(3.5, $result['total_customer_monologue']);
        $this->assertEquals(7, number_format($result['longest_user_monologue'], 2));
        $this->assertEquals(8.2, number_format($result['total_user_monologue'], 2));
        $this->assertEquals($userTalkPercentage, $result['user_talk_percentage_based_on_talk']);
    }

    public function testParseData() {
        $task = new Helper('tests/assets');
        $resultUser = $task->parseChannelDataTest(Task::USER);
        $resultCustomer = $task->parseChannelDataTest(Task::CUSTOMER);

        $this->assertEquals([
           0, 3.5, 10
        ], $resultUser['silenceEnd']);

        $this->assertEquals([
            1, 4.7
        ], $resultUser['silenceStart']);

        $this->assertEquals([
            1, 2.5, 5.3
        ], $resultUser['silenceDuration']);

        $this->assertEquals([
            0, 5, 9
        ], $resultCustomer['silenceEnd']);

        $this->assertEquals([
            2, 8
        ], $resultCustomer['silenceStart']);

        $this->assertEquals([
            2, 3, 1
        ], $resultCustomer['silenceDuration']);
    }

    public function testCalculateUserTalkBasedOnLastSilence() {

        $task = new Helper('tests/assets');
        $customerChannelData['silenceStart'] = [1, 5, 10, 45, 110];
        $userChannelData['silenceStart'] = [5, 8, 10, 46, 200];
        $parsedResult['user_talkDuration'] = 50;
        $result = $task->calculateUserTalkBasedOnLastSilenceTest($parsedResult, $customerChannelData, $userChannelData);

        $this->assertEquals("25.00", $result['user_talk_percentage']);
    }

    public function testCalculateUserTalkBasedOnTotalTalk() {
        $task = new Helper('tests/assets');
        $parsedResult['user_talkDuration'] = 50;
        $parsedResult['customer_talkDuration'] = 30;
        $result = $task->calculateUserTalkBasedOnTotalTalkTest($parsedResult);
        $this->assertEquals("62.50", $result['user_talk_percentage_based_on_talk']);
    }

    public function testCalculateClearSpeech() {
        $task = new Helper('tests/assets');
        $parsedResult['customer'] = [
            [0, 2],
            [5, 8],
            [9, 15],
            [17, 17.5],
        ];
        $parsedResult['user'] = [
            [0, 1],
            [3.5, 4.7],
            [10, 11],
            [18, 25],
        ];

        $result = $task->calculateClearSpeechTest($parsedResult);
        $this->assertEquals(3, $result['longest_customer_monologue']);
        $this->assertEquals(3.5, $result['total_customer_monologue']);
        $this->assertEquals(7, number_format($result['longest_user_monologue'], 2));
        $this->assertEquals(8.2, number_format($result['total_user_monologue'], 2));
    }

    public function testCalculateClearSpeechBlockLastUserSpeech() {
        $task = new Helper('tests/assets');
        $parsedResult['customer'] = [
            [0, 2],
            [5, 8],
            [9, 15],
            [17, 18.5],
        ];
        $parsedResult['user'] = [
            [0, 1],
            [3.5, 4.7],
            [10, 11],
            [18, 25],
        ];

        $result = $task->calculateClearSpeechTest($parsedResult);
        $this->assertEquals(3, $result['longest_customer_monologue']);
        $this->assertEquals(3, $result['total_customer_monologue']);
        $this->assertEquals(1.2, number_format($result['longest_user_monologue'], 2));
        $this->assertEquals(1.2, number_format($result['total_user_monologue'], 2));
    }

    public function testCalculateClearSpeechSequenceSpeech() {
        $task = new Helper('tests/assets');
        $parsedResult['customer'] = [
            [0, 2],
            [5, 8],
            [13, 13.5],
            [21, 22],
        ];
        $parsedResult['user'] = [
            [3, 4],
            [9, 12],
            [14, 20],
            [22.5, 23],
        ];

        $result = $task->calculateClearSpeechTest($parsedResult);
        $this->assertEquals(3, $result['longest_customer_monologue']);
        $this->assertEquals(6.5, $result['total_customer_monologue']);
        $this->assertEquals(6, number_format($result['longest_user_monologue'], 2));
        $this->assertEquals(10.5, number_format($result['total_user_monologue'], 2));
    }

}

class Helper extends Task
{
    public function __construct($assets = self::ASSETS_PATH)
    {
        parent::__construct($assets);
    }

    public function parseChannelDataTest($channel = null) {
        return $this->parseChannelData($channel);
    }

    public function calculateUserTalkBasedOnLastSilenceTest($parsedResult, $customerChannelData, $userChannelData) {
        return $this->calculateUserTalkBasedOnLastSilence($parsedResult, $customerChannelData, $userChannelData);
    }

    public function calculateUserTalkBasedOnTotalTalkTest($parsedResult) {
        return $this->calculateUserTalkBasedOnTotalTalk($parsedResult);
    }

    public function calculateClearSpeechTest($parsedResult) {
        return $this->calculateClearSpeech($parsedResult);
    }
}