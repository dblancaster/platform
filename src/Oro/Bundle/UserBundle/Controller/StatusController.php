<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\UserBundle\Entity\Status;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/status")
 */
class StatusController extends AbstractController
{
    /**
     * @Route("/", name="oro_user_status_list", defaults={"limit"=10})
     * @Template
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/create", name="oro_user_status_create")
     * @Template()
     * @param Request $request
     * @return array|Response
     */
    public function createAction(Request $request)
    {
        $result = false;

        if ($this->get('oro_user.form.handler.status')->process($this->getUser(), new Status(), true)) {
            $result = true;
        }

        if ($request->isXmlHttpRequest()) {
            if (!$result) {
                return $this->render(
                    'OroUserBundle:Status:statusForm.html.twig',
                    array(
                         'form' => $this->get('oro_user.form.status')->createView(),
                    )
                );
            } else {
                return new Response((string) $result);
            }
        } elseif ($result) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.status.message.saved')
            );

            return $this->redirect($this->generateUrl('oro_user_status_list'));
        }

        return array(
            'form' => $this->get('oro_user.form.status')->createView(),
        );
    }

    /**
     * @Route("/delete/{id}", name="oro_user_status_delete", requirements={"id"="\d+"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Status $status)
    {
        if ($this->get('oro_user.status_manager')->deleteStatus($this->getUser(), $status, true)) {
            $this->get('session')->getFlashBag()->add('success', 'Status deleted');
        } else {
            $this->get('session')->getFlashBag()->add('alert', 'Status is not deleted');
        }

        return $this->redirect($this->generateUrl('oro_user_status_list'));
    }

    /**
     * @Route("/set-current/{id}", name="oro_user_status_set_current", requirements={"id"="\d+"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function setCurrentStatusAction(Status $status)
    {
        $this->get('oro_user.status_manager')->setCurrentStatus($this->getUser(), $status);
        $this->get('session')->getFlashBag()->add('success', 'Status set');

        return $this->redirect($this->generateUrl('oro_user_status_list'));
    }

    /**
     * @Route("/clear-current", name="oro_user_status_clear_current")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function clearCurrentStatusAction()
    {
        $this->get('oro_user.status_manager')->setCurrentStatus($this->getUser());
        $this->get('session')->getFlashBag()->add('success', 'Status unset');

        return $this->redirect($this->generateUrl('oro_user_status_list'));
    }
}
