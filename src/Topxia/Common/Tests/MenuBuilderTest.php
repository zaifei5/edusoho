<?php
namespace Topxia\Common\Tests;

use Symfony\Component\Yaml\Yaml;
use Topxia\Service\Common\ServiceKernel;
use Topxia\Service\Common\BaseTestCase;
use Topxia\Common\MenuBuilder;

class MenuBuilderTest extends BaseTestCase
{
	public function testGetMenuByCode()
    {
    	$user = $this->getCurrentUser();
    	$permissions = $this->loadPermissions($user->toArray());
    	$user->setPermissions($permissions);

    	$menuBuilder = new MenuBuilder();
    	$menu = $menuBuilder->getMenuByCode('admin_user_show');
    }

    protected function loadPermissions($user)
    {
        if (empty($user['id'])) {
            return $user;
        }

        $menuBuilder = new MenuBuilder();
        $configs = $menuBuilder->getMenusYml();

        $res = array();
        foreach ($configs as $key => $config) {
            if(!file_exists($config)) {
                continue;
            }
            $menus = Yaml::parse(file_get_contents($config));
            if(empty($menus)) {
                continue;
            }

            $menus = $this->getMenusFromConfig($menus);
            $res = array_merge($res, $menus);
        }

        if (in_array('ROLE_SUPER_ADMIN', $user['roles'])) {
            return $res;
        }

        $permissionCode = array();
        foreach ($user['roles'] as $code) {
            $role = $this->getRoleService()->getRoleByCode($code);

            if (empty($role['data'])) {
                $role['data'] = array();
            }

            $permissionCode = array_merge($permissionCode, $role['data']);
        }

        $permissions = array();
        foreach ($res as $key => $value) {
            if (in_array($key, $permissionCode)) {
                $permissions[$key] = $res[$key];
            }
        }

        return $permissions;
    }

    protected function getMenusFromConfig($parents)
    {
        $menus = array();

        foreach ($parents as $key => $value) {
	        if(isset($parents[$key]['children'])) {
	        	$childrenMenu = $parents[$key]['children'];
	        	unset($parents[$key]['children']);

		        foreach ($childrenMenu as $childKey => $childValue) {
		        	$childValue["parent"] = $key;
		            $menus = array_merge($menus, $this->getMenusFromConfig(array($childKey => $childValue)));
		        }
	        }

	       	$menus[$key] = $value;
        }

        return $menus;
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getRoleService()
    {
        return $this->getServiceKernel()->createService('System.RoleService');
    }
}