<?php

describe("Test Response", function () {
    it("should be able to create a response", function () {
        $response = new Response(200, ["Content-Type" => "application/json"], "Hello, world!");
        expect($response)->toBeInstanceOf(Response::class);
    });

    it("should be able to create a response with body", function () {
        $response = new Response(200, ["Content-Type" => "application/json"], "Hello, world!");
        expect($response->getBody())->toBe("Hello, world!");
    });

    it("should be able to create a response with status code", function () {
        $response = new Response(200, ["Content-Type" => "application/json"], "Hello, world!");
        expect($response->getStatusCode())->toBe(200);
    });

    it("get response headers", function () {
        $response = new Response(200, ["Content-Type" => "application/json"], "Hello, world!");
        expect($response->getHeaders())->toBe(["Content-Type" => "application/json"]);
    });
    it("check if response has header", function () {
        $response = new Response(200, ["Content-Type" => "application/json"], "Hello, world!");
        expect($response->hasHeader("Content-Type"))->toBeTrue();
    });
});
