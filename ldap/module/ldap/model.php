<?php
/**
 * The model file of ldap module of ZenTaoPMS.
 *
 * @license     ZPL (http://zpl.pub/page/zplv11.html)
 * @author      TigerLau
 * @package     ldap
 * @link        http://www.zentao.net
 */
?>
<?php
class ldapModel extends model
{
    public function identify($host, $dn, $pwd)
    {
        $ret = '';
        $ds = ldap_connect($host);
    	if ($ds) {
    		ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3);
    		ldap_bind($ds, $dn, $pwd);

            $ret = ldap_error($ds);
    		ldap_close($ds);
    	}  else {
            $ret = ldap_error($ds);
        }

    	return $ret;
    }

    public function getUsers($config)
    {
	    $ds = ldap_connect($config->host);
	   // echo "ldap:".$config->host.$ds;
        if ($ds) {
            ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3);
            ldap_bind($ds, $config->bindDN, $config->bindPWD);

            $attrs = [$config->uid, $config->mail, $config->name];

            $rlt = ldap_search($ds, $config->baseDN, $config->searchFilter, $attrs);
            $data = ldap_get_entries($ds, $rlt);
            ldap_close($ds);
            return $data;
        }

        return null;
    }

    public function sync2db($config)
    {
        $ldapUsers = $this->getUsers($config);
        $user = new stdclass();
        $account = '';
        $i=0;
        for (; $i < $ldapUsers['count']; $i++) {         
            $user->account = $ldapUsers[$i][$config->uid][0];
            $user->email = $ldapUsers[$i][$config->mail][0];
            $user->realname = $ldapUsers[$i][$config->name][0];

            $account = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($user->account)->fetch('account');
            if ($account == $user->account) {
                $this->dao->update(TABLE_USER)->data($user)->where('account')->eq($user->account)->autoCheck()->exec();
            } else {
                $this->dao->insert(TABLE_USER)->data($user)->autoCheck()->exec();
	    }

            if(dao::isError()) 
            {
                echo js::error(dao::getError());
                die(js::reload('parent'));
            }
        }

        return $i;
    }
    public function getUser($config, $dn, $pwd)
    {
        $ds = ldap_connect($config->host);
        if ($ds) {
            ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3);
            ldap_bind($ds, $dn, $pwd);
            // echo ldap_error($ds);

            $attrs = [$config->uid, $config->mail, $config->name];

            $rlt = ldap_search($ds, $dn, $config->searchFilter, $attrs);
            $data = ldap_get_entries($ds, $rlt);
            // echo ldap_error($ds);

            ldap_close($ds);

            // echo 'cnt:'.$data['count'].'e';
            return $data;
        }  else {
            $ret = ldap_error($ds);
            echo $ret;

        }
        return null;
    }

    public function syncUser($config, $dn, $pwd, $acc)
    {
        $ldapUsers = $this->getUser($config, $dn, $pwd);    
        $user = new stdclass();
        $account = '';
        $i=0;
        if ( $ldapUsers && strstr($config->baseDN,'midea'))
        {
            $user->account = $acc;
            $user->email = $acc.'@midea.com';
            $user->realname = $acc;
            $account = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($user->account)->fetch('account');
            if ($account == $user->account) {
                // $this->dao->update(TABLE_USER)->data($user)->where('account')->eq($user->account)->autoCheck()->exec();
            } else {
                $this->dao->insert(TABLE_USER)->data($user)->autoCheck()->exec();
            }
        }
        else
        {
            for (; $i < $ldapUsers['count']; $i++) {         
                $user->account = $ldapUsers[$i][$config->uid][0];
                $user->email = $ldapUsers[$i][$config->mail][0];
                $user->realname = $ldapUsers[$i][$config->name][0];

                $account = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($user->account)->fetch('account');
                // echo $user->account.$user->email.$user->realname.':'.$account.'.end';
                if ($account == $user->account) {
                    $this->dao->update(TABLE_USER)->data($user)->where('account')->eq($user->account)->autoCheck()->exec();
                } else {
                    $this->dao->insert(TABLE_USER)->data($user)->autoCheck()->exec();
            }
        }
        if(dao::isError()) 
        {
            echo "dao error";
                echo js::error(dao::getError());
                die(js::reload('parent'));
            }
        }

        return $i;
    }
}
