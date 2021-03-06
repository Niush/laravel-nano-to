<?php

namespace Niush\NanoTo\Tests\Feature;

use Niush\NanoTo\Tests\TestCase;

class NamedRouteTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('app.env', 'local');
        $app['config']->set('app.debug', true);
    }

    /** @test */
    function success_page_is_accessible_from_named_route()
    {
        $this->get(route('nano-to-success', 'some-id'))
            ->assertSee('ok -');
    }

    /** @test */
    function cancel_page_is_accesible_from_named_route()
    {
        $this->get(route('nano-to-cancel', 'some-id'))
            ->assertSee('ko -');
    }

    /** @test */
    function webhook_page_is_accessible_from_named_route()
    {
        $this->post(route('nano-to-webhook', 'some-id'))
            ->assertSee('validation failed');

        $this->post(route('nano-to-webhook', 'some-id'), [
            "id" => "ffceexxxxxx",
            "status" => "complete",
            "amount" => "10",
            "method" => [
                "symbol" => "nano"
            ],
            "block" => [
                "type" => "receive",
                "hash" => "ABCD",
            ]
        ])
        ->assertSee('webhook -');
    }
}
