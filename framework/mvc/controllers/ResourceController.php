<?php
namespace regenix\mvc\controllers;

/**
 * Class for resources
 *
 * Class ResourceController
 * @package regenix\mvc\controllers
 */
abstract class ResourceController extends RESTController {
    abstract public function index();
    abstract public function create(array $data);
    abstract public function show($id);
    abstract public function update($id, array $data);
    abstract public function destroy($id);
}