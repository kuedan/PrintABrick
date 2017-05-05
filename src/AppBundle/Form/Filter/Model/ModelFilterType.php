<?php

namespace AppBundle\Form\Filter\Model;

use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModelFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('search', Filters\TextFilterType::class, [
            'apply_filter' => [$this, 'modelSearchCallback'],
            'label' => 'filter.model.search',
        ]);

        $builder->add('category', CategoryFilterType::class, [
            'add_shared' => function (FilterBuilderExecuterInterface $builderExecuter) {
                $builderExecuter->addOnce($builderExecuter->getAlias().'.category', 'c', function (QueryBuilder $filterBuilder, $alias, $joinAlias, $expr) {
                    $filterBuilder->leftJoin($alias.'.category', $joinAlias);
                });
            },
        ]);
    }

    public function getBlockPrefix()
    {
        return 'filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
        ]);
    }

    public function modelSearchCallback(QueryInterface $filterQuery, $field, $values)
    {
        if (empty($values['value']) || $values['value'] === '') {
            return null;
        }

        $expr = $filterQuery->getExpr();

        // expression that represent the condition
        $expression = $expr->orX(
            $expr->like('model.number', ':value'),
            $expr->like('model.name', ':value')
            //TODO filter by keywords
        );

        return $filterQuery->createCondition($expression, ['value' => '%'.$values['value'].'%']);
    }
}
