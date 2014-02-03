<?php

return array(
    'id' =>             'auth:ldap', # notrans
    'version' =>        '0.2',
    'name' =>           'LDAP Authentication and Lookup',
    'author' =>         'Jared Hancock',
    'description' =>    'Provides a configurable authentication backend
        which works against Microsoft Active Directory and OpenLdap
        servers',
    'url' =>            'http://www.osticket.com/plugins/auth/ldap',
    'plugin' =>         'authentication.php:LdapAuthPlugin',
    'includes' => array(
        # Install (into the PHAR) PEAR Net_LDAP2 into include/
        'lib/pear-pear.php.net/Net_LDAP2' => 'include',
    ),
);

?>
