<?php

namespace App\Form;

use App\Entity\Room;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roomKey', TextType::class, [
                'label' => 'Room Key',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter pass code',
                    'minlength' => 4,
                    'maxlength' => 22,
                    'pattern' => '[-_a-zA-Z0-9]{4,22}',
                    'value' => ''
                ]
            ])
            ->add('joinRoom', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
        ]);
    }
}
