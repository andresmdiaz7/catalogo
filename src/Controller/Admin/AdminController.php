<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 * @package App\Controller\Admin
 */


#[IsGranted('ROLE_ADMIN')]
abstract class AdminController extends AbstractController
{
    protected function addSuccessFlash(string $message): void
    {
        $this->addFlash('success', $message);
    }

    protected function addErrorFlash(string $message): void
    {
        $this->addFlash('error', $message);
    }

    protected function addWarningFlash(string $message): void
    {
        $this->addFlash('warning', $message);
    }

    protected function addInfoFlash(string $message): void
    {
        $this->addFlash('info', $message);
    }
} 