<?php

namespace CWB\Controller;

use CWB\Lib\View;

/**
 * Class Index pager
 * @author Felipe CWB <felipe.cwb@hotmail.com>
 */
class Index
{

	public function index()
	{
		// comments
		$view = new View(array('parser' => true));
		$view->addParams(array(
			'title' => 'Hello World',
			'framework' => 'CWB framework',
			'controllerFile' => highlight_file(__FILE__, true),
			'vdd' => true,
			'sub' => array('object' => 'testando UM SUB OBJETO'),
			'filmes' => array(
				array('tags' => array(
						array(
							'id' => 5,
							'nome' => 'Perigo'
						),
						array(
							'id' => 6,
							'nome' => 'Agitação'
						),
						array(
							'id' => 3,
							'nome' => 'Coragem'
						)
					),
					'nome' => 'Velozes e Furiosos',
					'categoria' => 'Corrida',
					'catalago' => array(
						'id' => 2,
						'nome' => 'Ação'
					),
				),
				array('tags' => array(
						array(
							'id' => 7,
							'nome' => 'Evangelistico'
						),
						array(
							'id' => 8,
							'nome' => 'Amor'
						),
						array(
							'id' => 9,
							'nome' => 'Paternidade'
						)
					),
					'nome' => 'Coragosos',
					'categoria' => 'Cristão',
					'catalago' => array(
						'id' => 3,
						'nome' => 'Gospel'
					),
				),
				array('tags' => array(
						array(
							'id' => 1,
							'nome' => 'Fé'
						),
						array(
							'id' => 2,
							'nome' => 'Confiança'
						),
						array(
							'id' => 3,
							'nome' => 'Coragem'
						)
					),
					'catalago' => array(
						'id' => 1,
						'nome' => 'Gospel'
					),
					'nome' => 'Desafiando Gigantes',
					'categoria' => 'Cristão',
				)
			),
			)
		)
		->setFile('index.php');

		echo $view;
	}
	
}
