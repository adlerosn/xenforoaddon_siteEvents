<?php
class siteEvents_router implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $controller = 'siteEvents_actions';

        return $router->getRouteMatch($controller, '', '');
    }
}
