<?php

namespace AppBundle\Form\Type;

use AppBundle\Form\Model\LoginModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;


class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'constraints' => new NotBlank()
            ])
            ->add('password', PasswordType::class, [
                'constraints' => new NotBlank()
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LoginModel::class,
            'csrf_protection' => false,
            'is_edit' => false,
            'extra_fields_message' => 'Extra fields sent! {{ extra_fields }}'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'login_type';
    }
}