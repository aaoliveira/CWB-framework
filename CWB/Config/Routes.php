<?php

/**
 * Rotas das paginas do site ou sistema
 * caso na referência de 'PATH_INFO' ou 'ORIG_PATH_INFO' for repassado 'nome-de-algo' ou 'nome.de.algo'
 * será transformado em 'nome_de_algo' para nomemclatura permitida de funções
 * funções do tipo protected não poderão ser acessadas.
 *
 * os dados repassados para dinamicos são como em sprintf() '%s' => 'controller/method/%s'
 * veja os padrões do sprintf();
 * a *key* e o *value* devem ter o mesmo numeros de coringas
 * todas as *key* dinamicas deve ser com o padrão '%s', 
 * pois se for '%d': '005' será '5' na rota e chamara (404) porque '005' é diferente de '5'
 * caso seja obrigatório os parametros é necessário a validação na *key*
 * para validação de dados passados basta fazer a validação no *value* Ex.: '%04d-%02d-%02d'
 */

return array(

	/**
	 * controller raiz caso não haja requisição PATH_INFO ou ORIG_PATH_INFO
	 */
	'_root_' => '',
	
	/**
	 * controller gonna show the page 404 - *please insert the method
	 */
	'_404_' => '',
	
);