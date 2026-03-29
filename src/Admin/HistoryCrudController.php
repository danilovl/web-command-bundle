<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Admin;

use Danilovl\WebCommandBundle\Entity\History;
use Doctrine\Common\Collections\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\{
    Crud,
    Action,
    Actions
};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    AssociationField,
    BooleanField,
    DateTimeField,
    IntegerField,
    NumberField,
    TextareaField,
    CodeEditorField
};

/**
 * @extends AbstractCrudController<History>
 */
class HistoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return History::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('History')
            ->setEntityLabelInPlural('Histories')
            ->setDefaultSort(['id' => Order::Descending->value]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id', 'ID')->onlyOnIndex();
        yield AssociationField::new('command', 'Command');
        yield BooleanField::new('async', 'Async');
        yield NumberField::new('duration', 'Duration (s)');
        yield IntegerField::new('exitCode', 'Exit code');
        yield TextareaField::new('errorMessage', 'Error message')->hideOnIndex();
        yield CodeEditorField::new('output', 'Output')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Run at');
    }
}
