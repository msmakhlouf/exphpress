<?php

namespace spec\Exphpress;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Exphpress\App');
    }    
    
    function it_builds_default_objects_when_initialized_without_dependencies()
    {
        $this->request->shouldHaveType('Symfony\Component\HttpFoundation\Request');
        $this->response->shouldHaveType('Symfony\Component\HttpFoundation\Response');
    }
    
    function it_is_initializable_by_injecting_objects(Response $res)
    {
        $this->beConstructedWith(Request::create('/'), $res);
        $this->shouldHaveType('Exphpress\App');
    }
    
    function it_register_a_global_callback_when_calling_listen()
    {
        $res = $this->listen(function($req, $res){
            $res->setContent('Hola!');
        });
        
        $res->getContent()->shouldEqual('Hola!');
    }
    
    function it_returns_an_empty_200_ok_response_by_default()
    {
        $res = $this->listen();
        
        $res->getContent()->shouldEqual('');
        $res->getStatusCode()->shouldEqual(200);
    }
    
    function it_can_use_listen_without_specifying_a_callback()
    {
        $this->listen()->getContent()->shouldEqual('');
    }
    
    function it_can_bind_GET_requests()
    {
        $this->beConstructedWith(Request::create('/hello/alex'));
        
        $this->get('/hello/{name}', function($req, $res){
            $res->setContent('Hola GET!');
        });
        
        $this->listen()->getContent()->shouldEqual('Hola GET!');
    }
    
    function it_wont_bind_routes_which_dont_match_the_HTTP_method()
    {
        $req = Request::create('/hello/alex');
        $req->setMethod('POST');
        $this->beConstructedWith($req);
        
        $this->get('/hello/{name}', function($req, $res){
            $res->setContent('Hola GET!');
        });
        
        $this->listen()->getStatusCode()->shouldEqual(405);
    }
    
    function it_return_500_if_nastyness_happens()
    {
        $this->beConstructedWith(Request::create('/hello'));
        
        $this->get('/hello', function($req, $res){
            throw new \Exception('HAHAHAHAHAHA');
        });
        
        $this->listen()->getStatusCode()->shouldEqual(500);
    }
    
    function it_returns_404_if_nothing_matches()
    {
        $this->beConstructedWith(Request::create('/hello/alex'));
        
        $this->get('/hola/{name}', function($req, $res){
            $res->setContent('Hola GET!');
        });
        
        $this->listen()->getStatusCode()->shouldEqual(404);
    }
    
    function it_can_register_a_middleware()
    {
        $this->beConstructedWith(Request::create('/hello/alex'));
        
        $this->get('/hola/{name}', function($req, $res){
            $res->setContent('Hola GET!');
        });
        
        $this->uses(function($req, $res){
            $res->setStatusCode(201);
        });
        
        $this->listen()->getStatusCode()->shouldEqual(201);
    }
    
    function it_can_concatenate_middlewares()
    {
        $this->beConstructedWith(Request::create('/hello/alex'));
        
        $this->get('/hello/{name}', function($req, $res, $next){
            $res->setContent('0');
            $next();
        });
        
        $this->uses(function($req, $res, $next){
            $res->setContent($res->getContent() . '1');
            $next();
        });
        
        $this->uses(function($req, $res, $next){
            $res->setContent($res->getContent() . '2');
        });
        
        // this middleware shouldnt be called a the previous
        // doesnt call $next()
        $this->uses(function($req, $res, $next){
            $res->setContent($res->getContent() . '3');
        });
        
        $this->listen()->getContent()->shouldEqual('012');
    }
}
