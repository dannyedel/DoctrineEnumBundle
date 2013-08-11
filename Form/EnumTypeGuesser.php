<?php

namespace Fresh\Bundle\DoctrineEnumBundle\Form;

use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\Guess;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EnumTypeGuesser extends DoctrineOrmTypeGuesser {
	/** Array holding 'ShortType' => 'Fully\Qualified\Class\Name'
		mappings for the enum classes */
	private $registeredTypes;

	public function __construct(ManagerRegistry $registry, array $types) {
		parent::__construct($registry);
		$this->registeredTypes=[];
		foreach ( $types as $type => $details ) {
			$this->registeredTypes[$type] = $details['class'];
		}
	}

	public function guessType( $class, $property ) {
		$ret = $this->getMetadata($class);

		/* No metadata for this class. We can't guess anything. */
		if ( !$ret )
			return null;

		list($metadata, $name) = $ret;
		$fieldType = $metadata->getTypeOfField($property);

		/* This is not one of the registered ENUM types. */
		if ( ! isset ( $this->registeredTypes[$fieldType] ) )
			return null;

		$className = $this->registeredTypes[$fieldType];
		if ( !class_exists($className) ) {
			throw new \Exception( sprintf(
				_("Enum class %s is registered as %s, but that class does not exist"),
				$fieldType, $className) );
		}

		/** Get the choices from the fully qualified class name */
		$parameters = [ 'choices' => $className::getChoices() ];

		$parameters['required'] = ! $metadata->isNullable($property);
		/** If the column is nullable, a blank entry is acceptable. */

		/** FIXME: Display a locale-specific string instead */
		$parameters['empty_value'] = "Bitte wählen";

		return new TypeGuess(
			'choice',
			$parameters,
			Guess::VERY_HIGH_CONFIDENCE );
	}
}
