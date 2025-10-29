<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Subscriber;

use Shopware\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * ACL Subscriber for KI Data Selector
 *
 * Ensures only administrators or users with proper privileges
 * can access the KI Data Selector endpoints.
 *
 * @package Coding9\KIDataSelector\Core\Subscriber
 */
class AdminAclSubscriber implements EventSubscriberInterface
{
    private const KIDATA_ROUTES = [
        'api.action.kidata.query',
        'api.action.kidata.export'
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['checkAcl', 10]
        ];
    }

    /**
     * Check ACL permissions for KI Data Selector routes
     *
     * @param RequestEvent $event
     */
    public function checkAcl(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Only check our routes
        if (!in_array($route, self::KIDATA_ROUTES, true)) {
            return;
        }

        $context = $request->attributes->get('sw-context');

        if (!$context instanceof Context) {
            return;
        }

        $source = $context->getSource();

        // Must be admin API source
        if (!$source instanceof AdminApiSource) {
            throw new AccessDeniedHttpException('Access denied: Admin API access required');
        }

        // Check if user is admin (admins have all privileges)
        $permissions = $source->getPermissions();

        if (in_array(AclRoleDefinition::ALL_ROLE_KEY, $permissions, true)) {
            return; // Admin has access
        }

        // Check for specific privilege
        $requiredPrivileges = [
            'kidata_query_log:read',
            'kidata_query_log:create',
            'kidata_saved_query:read'
        ];

        $hasAccess = false;
        foreach ($requiredPrivileges as $privilege) {
            if (in_array($privilege, $permissions, true)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            throw new AccessDeniedHttpException('Access denied: Insufficient privileges for KI Data Selector');
        }
    }
}
