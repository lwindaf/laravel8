<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use function QiniuConf\explodeUpToken;

class TcpServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tcp:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a TCP server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $loop = Factory::create();
        $socket = new SocketServer('0.0.0.0:8765', $loop);
        $socket->on('connection', function ($conn) {
            $conn->on('data', function ($data) use ($conn) {
                // 处理数据
                $response = 'Received: ' . $data;


                $dataArr = explode(' ', $data);
                $act = isset($dataArr[0]) ? trim($dataArr[0]) : '';
                $x = isset($dataArr[1]) ? intval($dataArr[1]) : 0;
                $y = isset($dataArr[2]) ? intval($dataArr[2]) : 0;
                //$conn->write($act . $x . $y);

                //乘法计算
                if(!empty($act) && $act == 'mul') {
                    $res = $x * $y;
                    $conn->write(strval($res));
                } else if(!empty($act) && $act == 'incr') {  //自增
                    $res = $x + 1;
                    $conn->write(strval($res));
                } else if(!empty($act) && $act == 'div') {  //除法
                    $res = $x / $y;
                    $conn->write(strval($res));
                } else if(!empty($act) && $act == 'conv_tree') {  //处理json

                    $res = $this->parseJson();
                    $conn->write($res);
                }

                //$conn->close();
            });
            $conn->on('error', function ($e) {
                Log::error($e->getMessage());
            });
        });
        $this->info('TCP server started.');
        $loop->run();
    }

    /*
     * 转换成三级数结构
     */
    public function parseJson() {
        $jsonStr = '[
            {
                "id": 200002538,
                "name": "空心菜类",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,空心菜类"
            },
            {
                "id": 200002537,
                "name": "香菜类",
                "level": 3,
                "namePath": "蔬菜/豆制品,葱姜蒜椒/调味菜,香菜类"
            },
            {
                "id": 200002536,
                "name": "紫苏/苏子叶",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,紫苏/苏子叶"
            },
            {
                "id": 200002543,
                "name": "乌塌菜/塌菜/乌菜",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,乌塌菜/塌菜/乌菜"
            },
            {
                "id": 200002542,
                "name": "菜心/菜苔类",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,菜心/菜苔类"
            },
            {
                "id": 200002540,
                "name": "马兰头/马兰/红梗菜",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,马兰头/马兰/红梗菜"
            },
            {
                "id": 200002531,
                "name": "苋菜类",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,苋菜类"
            },
            {
                "id": 200002528,
                "name": "其他叶菜类",
                "level": 3,
                "namePath": "蔬菜/豆制品,叶菜类,其他叶菜类"
            }
        ]';

        $jsonArr = json_decode($jsonStr, true);
        $firstNameArr = [];  //一级菜品列表
        foreach($jsonArr as $data) {
            $namePath = explode(',', $data['namePath']);
            if(!isset($firstNameArr[$namePath[0]])) {  //加入一级列表
                $randNum = $this->rand();  //随机10位
                $firstNameArr[$namePath[0]] = [
                    'id' =>  $randNum,
                    'id_path' => ',' . $randNum . ',',
                    'is_leaf' => 2,
                    'level' => 1,
                    'name' => $namePath[0],
                    'name_path' => $namePath[0],
                    'parent_id' => 0,
                    'children' => [],  //子级列表

                ];
            }
        }

        //找到其二级菜品列表
        foreach($jsonArr as $data) {
            $namePath = explode(',', $data['namePath']);
            if(isset($firstNameArr[$namePath[0]]) && !isset($firstNameArr[$namePath[0]]['children'][$namePath[1]])) {  //加入子集中
                $firstNameArr[$namePath[0]]['children'][$namePath[1]] = [
                    $randNum = $this->rand(),  //随机10位
                    'id' =>  $randNum,
                    'id_path' => $firstNameArr[$namePath[0]]['id_path'] . $randNum . ',',
                    'is_leaf' => 2,
                    'level' => 2,
                    'name' => $namePath[1],
                    'name_path' => $namePath[0] . ',' . $namePath[1],
                    'parent_id' => $firstNameArr[$namePath[0]]['id'],
                    'children' => [],  //子级列表
                ];
            }
        }

        //找到其三级菜品列表
        foreach($jsonArr as $data) {
            $namePath = explode(',', $data['namePath']);
            if(isset($firstNameArr[$namePath[0]]) && isset($firstNameArr[$namePath[0]]['children'][$namePath[1]]) && !isset($firstNameArr[$namePath[0]]['children'][$namePath[1]]['children'][$namePath[0]])) {  //加入子集中
                $firstNameArr[$namePath[0]]['children'][$namePath[1]]['children'][] = [
                    'id' =>  $data['id'],
                    'id_path' =>  $firstNameArr[$namePath[0]]['children'][$namePath[1]]['id_path'] . $data['id'] . ',',
                    'is_leaf' => 1,
                    'level' => 3,
                    'name' => $namePath[1],
                    'name_path' => $namePath[0] . ',' . $namePath[1] . ',' . $namePath[2],
                    'parent_id' => $firstNameArr[$namePath[0]]['children'][$namePath[1]]['id'],
                ];
            }
        }

        //print_r($firstNameArr);
        //汉字键值转数字
        $firstNameArr = array_values($firstNameArr);
        $firstNameArr[0]['children'] = array_values($firstNameArr[0]['children']);
        return json_encode($firstNameArr);

    }

    /*
     * 生成10位随机数
     */
    private function rand() {
        $metas = range(0, 9);
        $metas = array_merge($metas, range('A', 'Z'));
        $metas = array_merge($metas, range('a', 'z'));
        $str = '';
        for ($i = 0; $i < 10; $i++) {
            $str .= $metas[rand(0, count($metas) - 1)];
        }
        return $str;
    }

}
