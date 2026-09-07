<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Gateways\MiaoxinGateway;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Tests\TestCase;

class MiaoxinGatewayTest extends TestCase
{
    public function test_send_content()
    {
        $config = [
            'account' => 'mock-account',
            'secret' => 'mock-secret',
        ];
        $gateway = \Mockery::mock(MiaoxinGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'account' => 'mock-account',
            'mobiles' => 18188888888,
            'content' => 'This is a test message.',
        ];
        $gateway->shouldReceive('post')
            ->with(MiaoxinGateway::ENDPOINT_HOST.MiaoxinGateway::SEND_PATH, \Mockery::subset($params))
            ->andReturn([
                'code' => MiaoxinGateway::SUCCESS_CODE,
                'msg' => '发送成功',
                'total' => 1,
                'results' => [
                    ['mobile' => '18188888888', 'order_id' => '1839347820929302091', 'code' => 0, 'msg' => '处理中'],
                ],
            ], [
                'code' => -9102,
                'msg' => '账号余额不足',
            ])->times(2);

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);

        $this->assertSame([
            'code' => MiaoxinGateway::SUCCESS_CODE,
            'msg' => '发送成功',
            'total' => 1,
            'results' => [
                ['mobile' => '18188888888', 'order_id' => '1839347820929302091', 'code' => 0, 'msg' => '处理中'],
            ],
        ], $gateway->send(new PhoneNumber(18188888888), $message, $config));

        $this->expectException(GatewayErrorException::class);
        $this->expectExceptionCode(-9102);
        $this->expectExceptionMessage('账号余额不足');

        $gateway->send(new PhoneNumber(18188888888), $message, $config);
    }

    public function test_send_fixed_signature()
    {
        $config = [
            'account' => 'mock-account',
            'secret' => 'mock-secret',
            'signature_id' => 123,
        ];
        $gateway = \Mockery::mock(MiaoxinGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'account' => 'mock-account',
            'mobiles' => 18188888888,
            'content' => 'This is a test message.',
            'signatureId' => 123,
        ];
        $gateway->shouldReceive('post')
            ->with(MiaoxinGateway::ENDPOINT_HOST.MiaoxinGateway::FIXED_SIGNATURE_PATH, \Mockery::subset($params))
            ->andReturn([
                'code' => MiaoxinGateway::SUCCESS_CODE,
                'msg' => '发送成功',
                'total' => 1,
                'results' => [],
            ])->once();

        $message = new Message(['content' => 'This is a test message.']);
        $config = new Config($config);

        $this->assertSame([
            'code' => MiaoxinGateway::SUCCESS_CODE,
            'msg' => '发送成功',
            'total' => 1,
            'results' => [],
        ], $gateway->send(new PhoneNumber(18188888888), $message, $config));
    }

    public function test_send_template()
    {
        $config = [
            'account' => 'mock-account',
            'secret' => 'mock-secret',
        ];
        $gateway = \Mockery::mock(MiaoxinGateway::class.'[post]', [$config])->shouldAllowMockingProtectedMethods();

        $params = [
            'account' => 'mock-account',
            'mobiles' => 18188888888,
            'templateId' => 'mock-tpl-id',
            'param1' => '1234',
            'param2' => '张三',
        ];
        $gateway->shouldReceive('post')
            ->with(MiaoxinGateway::ENDPOINT_HOST.MiaoxinGateway::TEMPLATE_PATH, \Mockery::subset($params))
            ->andReturn([
                'code' => MiaoxinGateway::SUCCESS_CODE,
                'msg' => '发送成功',
                'total' => 1,
                'results' => [],
            ])->once();

        $message = new Message([
            'template' => 'mock-tpl-id',
            'data' => [
                'code' => '1234',
                'name' => '张三',
            ],
        ]);
        $config = new Config($config);

        $this->assertSame([
            'code' => MiaoxinGateway::SUCCESS_CODE,
            'msg' => '发送成功',
            'total' => 1,
            'results' => [],
        ], $gateway->send(new PhoneNumber(18188888888), $message, $config));
    }

    public function test_generate_token()
    {
        $gateway = new MiaoxinGateway(['account' => 'mock-account', 'secret' => 'mock-secret']);

        $method = new \ReflectionMethod(MiaoxinGateway::class, 'generateToken');
        $method->setAccessible(true);

        $this->assertSame(
            sha1('account=mock-account&ts=20260907120000&secret=mock-secret'),
            $method->invoke($gateway, 'mock-account', 'mock-secret', '20260907120000')
        );
    }
}
