<?php

use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    protected $task;

    public function testGetAllData()
    {

        $this->task = new \App\Task('tests/assets');
        $result = $this->task->getAllData();
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
        $this->task = new \App\Task('tests/assets-complex');
        $result = $this->task->getAllData();
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
}