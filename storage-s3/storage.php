<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class S3StoragePluginConfig extends PluginConfig {

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    function translate() {
        if (!method_exists('Plugin', 'translate')) {
            return array(
                function($x) { return $x; },
                function($x, $y, $n) { return $n != 1 ? $y : $x; },
            );
        }
        return Plugin::translate('auth-ldap');
    }

    function getOptions() {
        list($__, $_N) = self::translate();
        return array(
            'bucket' => new TextboxField(array(
                'label' => $__('S3 Bucket'),
                'configuration' => array('size'=>40),
            )),
            'aws-region' => new ChoiceField(array(
                'label' => $__('AWS Region'),
                'choices' => array(
                    '' => 'US Standard',
					'us-east-1' => 'US East (Northern Virginia)',
					'us-west-2' => 'US West (Oregon) Region',
					'us-west-1' => 'US West (Northern California) Region',
					'eu-west-1' => 'EU (Ireland) Region',
					'eu-north-1' => 'Europe (Stockholm) Region',
					'ap-southeast-1' => 'Asia Pacific (Singapore) Region',
					'ap-southeast-2' => 'Asia Pacific (Sydney) Region',
					'ap-northeast-1' => 'Asia Pacific (Tokyo) Region',
					'sa-east-1' => 'South America (Sao Paulo) Region',
					'US East (Ohio)' => 'us-east-2',
					'US East (N. Virginia)' => 'us-east-1',
					'US West (N. California)' => 'us-west-1',
					'US West (Oregon)' => 'us-west-2',
					'Africa (Cape Town)' => 'af-south-1',
					'Asia Pacific (Hong Kong)' => 'ap-east-1',
					'Asia Pacific (Mumbai)' => 'ap-south-1',
					'Asia Pacific (Osaka-Local)' => 'ap-northeast-3',
					'Asia Pacific (Seoul)' => 'ap-northeast-2',
					'Asia Pacific (Singapore)' => 'ap-southeast-1',
					'Asia Pacific (Sydney)' => 'ap-southeast-2',
					'Asia Pacific (Tokyo)' => 'ap-northeast-1',
					'Canada (Central)' => 'ca-central-1',
					'China (Beijing)' => 'cn-north-1',
					'China (Ningxia)' => 'cn-northwest-1',
					'Europe (Frankfurt)' => 'eu-central-1',
					'Europe (Ireland)' => 'eu-west-1',
					'Europe (London)' => 'eu-west-2',
					'Europe (Milan)' => 'eu-south-1',
					'Europe (Paris)' => 'eu-west-3',
					'Europe (Stockholm)' => 'eu-north-1',
					'Middle East (Bahrain)' => 'me-south-1',
					'South America (São Paulo)' => 'sa-east-1',
                ),
                'default' => '',
            )),
            'acl' => new ChoiceField(array(
                'label' => $__('Default ACL for Attachments'),
                'choices' => array(
                    '' => $__('Use Bucket Default'),
                    'private' => $__('Private'),
                    'public-read' => $__('Public Read'),
                    'public-read-write' => $__('Public Read and Write'),
                    'authenticated-read' => $__('Read for AWS authenticated Users'),
                    'bucket-owner-read' => $__('Read for Bucket Owners'),
                    'bucket-owner-full-control' => $__('Full Control for Bucket Owners'),
                ),
                'default' => '',
            )),

            'access-info' => new SectionBreakField(array(
                'label' => $__('Access Information'),
            )),
            'aws-key-id' => new TextboxField(array(
                'required' => true,
                'configuration'=>array('length'=>64, 'size'=>40),
                'label' => $__('AWS Access Key ID'),
            )),
            'secret-access-key' => new TextboxField(array(
                'widget' => 'PasswordWidget',
                'required' => false,
                'configuration'=>array('length'=>64, 'size'=>40),
                'label' => $__('AWS Secret Access Key'),
            )),
        );
    }

    function pre_save(&$config, &$errors) {
        list($__, $_N) = self::translate();
        $credentials = array(
            'key' => $config['aws-key-id'],
            'secret' => $config['secret-access-key']
                ?: Crypto::decrypt($this->get('secret-access-key'), SECRET_SALT,
                        $this->getNamespace()),
        );
        if ($config['aws-region'])
            $credentials['region'] = $config['aws-region'];
			
        if (!$credentials['secret'])
            $this->getForm()->getField('secret-access-key')->addError(
                $__('Secret access key is required'));

        $s3 = Aws\S3\S3Client::factory($credentials);

        try {
            $s3->headBucket(array('Bucket'=>$config['bucket']));
        }
        catch (Aws\S3\Exception\AccessDeniedException $e) {
            $errors['err'] = sprintf(
                /* The %s token will become an upstream error message */
                $__('User does not have access to this bucket: %s'), (string)$e);
        }
        catch (Aws\S3\Exception\NoSuchBucketException $e) {
            $this->getForm()->getField('bucket')->addError(
                $__('Bucket does not exist'));
        }

        if (!$errors && $config['secret-access-key'])
            $config['secret-access-key'] = Crypto::encrypt($config['secret-access-key'],
                SECRET_SALT, $this->getNamespace());
        else
            $config['secret-access-key'] = $this->get('secret-access-key');

        return true;
    }
}
