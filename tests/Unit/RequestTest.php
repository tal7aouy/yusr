<?php

describe("Test Request", function () {
    it("should be able to create a request", function () {
        $request = new Request("GET", "https://jsonplaceholder.typicode.com/posts");
        expect($request)->toBeInstanceOf(Request::class);
    });

    it("should be able to create a request with headers", function () {
        $request = new Request("GET", "https://jsonplaceholder.typicode.com/posts");
        $request->withHeader("Accept", "application/json");
        expect($request->getHeader("Accept"))->toBe("application/json");
    });

    it("should be able to create a request with body", function () {
        $request = new Request("GET", "https://jsonplaceholder.typicode.com/posts");
        $request->withBody(new Stream("Hello, world!"));
        expect($request->getBody())->toBe("Hello, world!");
    });

    it("should be able to create a request with uri", function () {
        $request = new Request("GET", "https://jsonplaceholder.typicode.com/posts");
        $request->withUri(new Uri("https://jsonplaceholder.typicode.com/posts"));
        expect($request->getUri())->toBe("https://jsonplaceholder.typicode.com/posts");
    });

    it("should be able to create a request with query params", function () {
        $request = new Request("GET", "https://jsonplaceholder.typicode.com/posts");
        $request->withQueryParams(["page" => 1]);
        expect($request->getQueryParams())->toBe(["page" => 1]);
    });

    it("should be able to create a request with method", function () {
        $request = new Request("GET", "https://jsonplaceholder.typicode.com/posts");
        $request->withMethod("POST");
        expect($request->getMethod())->toBe("POST");
    });
});
