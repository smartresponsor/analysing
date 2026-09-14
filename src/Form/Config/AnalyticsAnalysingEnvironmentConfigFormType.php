<?php

declare(strict_types=1);

namespace App\Analysing\Form\Config;

use App\Analysing\DTO\Config\AnalyticsEnvironmentConfigDataDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AnalyticsAnalysingEnvironmentConfigFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clickhouseBase', TextType::class, [
                'label' => 'ANALYSING_CLICKHOUSE_BASE',
                'required' => true,
            ])
            ->add('clickhouseUser', TextType::class, [
                'label' => 'ANALYSING_CLICKHOUSE_USER',
                'required' => true,
            ])
            ->add('idempotencyEnabled', CheckboxType::class, [
                'label' => 'ANALYSING_IDEMPOTENCY_ENABLED',
                'required' => false,
            ])
            ->add('idempotencyRequired', CheckboxType::class, [
                'label' => 'ANALYSING_IDEMPOTENCY_REQUIRED',
                'required' => false,
            ])
            ->add('authRequired', CheckboxType::class, [
                'label' => 'ANALYSING_AUTH_REQUIRED',
                'required' => false,
            ])
            ->add('authPublicRead', CheckboxType::class, [
                'label' => 'ANALYSING_AUTH_PUBLIC_READ',
                'required' => false,
            ])
            ->add('rateLimitEnabled', CheckboxType::class, [
                'label' => 'ANALYSING_RATE_LIMIT_ENABLED',
                'required' => false,
            ])
            ->add('rateLimitWindowSeconds', IntegerType::class, [
                'label' => 'ANALYSING_RATE_LIMIT_WINDOW_SECONDS',
                'required' => true,
                'empty_data' => '60',
            ])
            ->add('rateLimitDefaultWriteLimit', IntegerType::class, [
                'label' => 'ANALYSING_RATE_LIMIT_DEFAULT_WRITE_LIMIT',
                'required' => true,
                'empty_data' => '60',
            ])
            ->add('save', SubmitType::class, ['label' => 'Save pending'])
            ->add('apply', SubmitType::class, ['label' => 'Apply now', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnalyticsEnvironmentConfigDataDTO::class,
        ]);
    }
}
