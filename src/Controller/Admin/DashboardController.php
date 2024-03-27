<?php

namespace App\Controller\Admin;

use App\Entity\Idea;
use App\Entity\Member;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Cadeau');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Membres', 'fa fa-users');
        yield MenuItem::linkToCrud('Liste', 'fa fa-users', Member::class);

        yield MenuItem::section('Idées', 'fa fa-users');
        yield MenuItem::linkToCrud('Liste', 'fa fa-users', Idea::class);
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd MMMM y')
            ->setTimeFormat(DateTimeField::FORMAT_MEDIUM)
            ->setTimezone('Europe/Paris')
            ->setPaginatorPageSize(20)
            ;
    }
}
