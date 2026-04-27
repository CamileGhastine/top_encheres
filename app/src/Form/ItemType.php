<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Item;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ItemType extends AbstractType
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('description', TextareaType::class,[
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('startingPrice', NumberType::class, [
                'constraints' => [
                    new Assert\Positive()
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    Item::TRANSLATED_STATUS[Item::UNPUBLISHED] => Item::UNPUBLISHED,
                    Item::TRANSLATED_STATUS[Item::PUBLISHED] => Item::PUBLISHED,
                ],
                'constraints' => [ 
                    new Assert\Choice(['choices' => [Item::UNPUBLISHED, Item::PUBLISHED]])
                ]
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'constraints' =>[ 
                    new Assert\Count(['min' => 1])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}
