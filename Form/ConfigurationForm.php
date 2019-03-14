<?php


namespace ElasticProduct\Form;


use ElasticProduct\ElasticProduct;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigurationForm extends BaseForm
{
    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return 'elastic_product_configuration';
    }

    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     */
    protected function buildForm()
    {
        $this->formBuilder
            ->add('host', 'text', array(
                'data' => ElasticProduct::getConfigValue('host'),
                'required' => false,
                'attr' => ['placeholder' => 'localhost'],
                'label' => Translator::getInstance()->trans('Host', array(), ElasticProduct::DOMAIN_NAME),
                'label_attr' => array(
                    'for' => 'host'
                )
            ))
            ->add('port', 'text', array(
                'data' => ElasticProduct::getConfigValue('port'),
                'required' => false,
                'attr' => ['placeholder' => '9200'],
                'label' => Translator::getInstance()->trans('Port', array(), ElasticProduct::DOMAIN_NAME),
                'label_attr' => array(
                    'for' => 'port'
                )
            ))
            ->add('username', 'text', array(
                'data' => ElasticProduct::getConfigValue('username'),
                'required' => false,
                'label' => Translator::getInstance()->trans('Username', array(), ElasticProduct::DOMAIN_NAME),
                'label_attr' => array(
                    'for' => 'username'
                )
            ))
            ->add('password', PasswordType::class, array(
                'data' => ElasticProduct::getConfigValue('password'),
                'required' => false,
                'label' => Translator::getInstance()->trans('Password', array(), ElasticProduct::DOMAIN_NAME),
                'label_attr' => array(
                    'for' => 'password'
                )
            ))
            ->add('index_prefix', 'text', array(
                'data' => ElasticProduct::getConfigValue('index_prefix'),
                'required' => false,
                'attr' => ['placeholder' => 'my_website_name'],
                'label' => Translator::getInstance()->trans('Index prefix', array(), ElasticProduct::DOMAIN_NAME),
                'label_attr' => array(
                    'for' => 'index_prefix'
                )
            ));
    }
}