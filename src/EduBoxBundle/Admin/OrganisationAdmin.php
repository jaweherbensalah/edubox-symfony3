<?php


namespace EduBoxBundle\Admin;


use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;

final class OrganisationAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'organisation';
    protected $baseRouteName = 'edubox.admin.organisation';
    protected $translationDomain = 'EduBoxBundle';

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clear();
        $collection->add('list', 'edit');
    }
}