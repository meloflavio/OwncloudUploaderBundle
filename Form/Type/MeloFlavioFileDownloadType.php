<?php


namespace MeloFlavio\OwncloudUploaderBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeloFlavioInternalFileDownloadType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'change_class' => 'btn-link',
            'target' => '_blank',
            'label_render' => null,
            'property_path' => null,
            'sonata_field_description' => null,
            'auto_initialize' => false,
            'icon_only' => false,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'change_class' => $options['change_class'],
            'target' => $options['target'],
            'label' => 'Anexos',
            'icon_only' => $options['icon_only'],
        ]);
    }
    public function getParent()
    {
        return TextType::class;
    }

}