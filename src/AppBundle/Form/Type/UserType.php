<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;


class UserType extends AbstractType
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
            ->add('email', EmailType::class, [
                'constraints' => new NotBlank()
            ])
            ->add('name', TextType::class)
            ->add('roles', ChoiceType::class, [
                'choices'  => array(
                    'ROLE_USER',
                    'ROLE_ADMIN'
                ),
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'is_edit' => false,
            'extra_fields_message' => 'Extra fields sent! {{ extra_fields }}',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_user_type';
    }
}
