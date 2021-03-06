<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("activity_email_suggestion")
 * @NamePrefix("oro_api_")
 */
class EmailActivitySuggestionController extends RestGetController
{
    /**
     * Suggests entities associated with the email activity.
     *
     * @param int $id The id of the email entity.
     *
     * @Get("/activities/emails/{id}/suggestions", requirements={"id"="\d+"})
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *      name="exclude-current-user",
     *      requirements="true|false",
     *      nullable=true,
     *      strict=true,
     *      default="false",
     *      description="Indicates whether the current user should be excluded from the result."
     * )
     *
     * @ApiDoc(
     *      description="Suggests entities associated with the email activity",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request, $id)
    {
        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);
        $excludeCurrentUser = filter_var($request->get('exclude-current-user'), FILTER_VALIDATE_BOOLEAN);

        $data = $this->getManager()->getSuggestionResult($id, $page, $limit, $excludeCurrentUser);

        return $this->buildResponse($data['result'], self::ACTION_LIST, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_email.manager.email_activity_suggestion.api');
    }
}
