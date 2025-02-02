<?php

/**
 * Realiza a importação de código PHP com mensagens de erro personalizadas.
 * O objetivo desta função é evitar a repetição da função `include` em 
 * várias linhas, possibilitando o uso de uma única linha para incluir 
 * diversos scripts de lugares diferentes. É possível importar scripts 
 * que estão dentro de uma pasta ou scripts individuais espalhados em 
 * muitas pastas.
 * @param string|array $list Lista de scripts a serem importados. A 
 * quantidade de scritps pode ser indefinida. Podem ser especificados 
 * tanto nomes de pastas quanto arquivos individuais. Os scripts serão 
 * incluídos na ordem que forem especificados. Se for uma pasta, 
 * incluirá todos os scripts PHP dentro dessa pasta. Utilize sempre o 
 * caminho relativo dos arquivos e diretórios.
 * @return array Retorna uma Array vazia se a operação for bem-sucedida, 
 * sem erros. Retorna uma Array com detalhes dos erros, quais arquivos 
 * não puderam ser importados e por quê. A estrutura da Array de retorno 
 * é a seguinte:
 * - `item`: Nome do arquivo incluído;
 * - `message`: Mensagem de erro relacionada;
 * - `details`: Detalhes do erro vindos do PHP;
 * 
 * Dentro de `details`, temos:
 * - `internal`: Mensagem interna do erro;
 * - `file`: Nome do script PHP gerador do erro;
 * - `line`: Linha de código que acionou o erro;
 * - `trace`: Caminho de funções que levou ao erro:
 * @author Siael Alves
 * @copyright © Copyright 2025, Siael Alves
 */
function import ( ...$list ) : array {

 /** @var array $result Resultado da função. Contém uma lista de quais arquivos 
  * foram incluídos e dos motivos pelos quais outros arquivos não puderam ser 
  * incluídos. Permanece vazia caso nenhum erro tenha ocorrido. */
 $result = [ ] ;

 if ( gettype ( $list ) == "array" ) {

  array_map ( function ( $item ) use ( &$result ) { 

   /* Se o item de `$list` for um diretório . . . */
    if ( is_dir ( $item ) ) {

     $directory = $item ;

     if ( !file_exists ( $directory ) ) { 
      $ex = new Error ( "O diretório <code>$directory</directory> não existe." ) ;
      array_push ( $result , [
       "item" => $directory ,
       "message" => "O diretório <code>$directory</directory> não existe." ,
       "details" => [
        "internal" => $ex->getMessage ( ) ,
        "file" => $ex->getFile ( ) ,
        "line" => $ex->getLine ( ) ,
        "trace" => $ex->getTraceAsString ( ) ,
       ]
      ] ) ;

      return "" ;
     }

     // Obtém os arquivos dentro de $item (diretório)
     $files = scandir ( $directory ) ;

     // Verifica cada arquivo dentro do diretório para ver se é válido. Se não for, 
     //  adiciona uma entrada à array `$result`
     $files = array_map ( function ( $file ) use ( &$result , &$directory ) {

      if ( str_starts_with ( $file , "." ) ) { 
       return null ;
      }

      if ( !file_exists ( $directory . "/" . $file ) ) {
       $ex = new Error ( "O arquivo <code>$directory/$file</code> não existe." ) ;

       array_push ( $result , [
        "item" => $file ,
        "message" => "O arquivo <code>$directory/$file</code> não existe." ,
        "details" => [
         "internal" => $ex->getMessage ( ) ,
         "file" => $ex->getFile ( ) ,
         "line" => $ex->getLine ( ) ,
         "trace" => $ex->getTraceAsString ( ) ,
        ]
       ] ) ;
   
       return null ;
      }

      if ( !str_ends_with ( $directory . "/" . $file , ".php" ) ) {
       $ex = new Error ( "O arquivo <code>$directory/$file</code> é um arquivo inválido." ) ;

       array_push ( $result , [
        "item" => $file ,
        "message" => "O arquivo <code>$directory/$file</code> não parece ser um script php válido porque não 
         possui a extensão <strong>.php</strong>. Apenas arquivos <strong>.php</strong> são permitidos." ,
         "details" => [
          "internal" => $ex->getMessage ( ) ,
          "file" => $ex->getFile ( ) ,
          "line" => $ex->getLine ( ) ,
          "trace" => $ex->getTraceAsString ( ) ,
         ]
       ] ) ;

       return null ;
      }

      return $directory . "/" . $file ;

     } , $files ) ;

     // Remove elementos `null` da array `$files`. Evita erro ao tentar 
     //  incluir um arquivo de nome vazio.
     $files = array_filter ( $files, function ( $file ) {
      return ( $file != null ) ;
     } ) ;

     // Verifica se restou algum arquivo dentro da lista.
     if ( count ( $files ) == 0 ) {
      echo "Não foram encontrados arquivos <code>.php</code> no diretório $directory." ;

      $ex = new Error ( "Não foram encontrados arquivos <code>.php</code> no diretório $directory." ) ;

      array_push ( $result , [
       "item" => $directory ,
       "message" => "Não foram encontrados arquivos <code>.php</code> no diretório $directory." ,
       "details" => [
        "internal" => $ex->getMessage ( ) ,
        "file" => $ex->getFile ( ) ,
        "line" => $ex->getLine ( ) ,
        "trace" => $ex->getTraceAsString ( ) ,
       ]
      ] ) ;

      return $result ; // SAÍDA 1 DA FUNÇÃO
     }
     
     // Realiza a inserção de cada arquivo do diretório no código
     array_map ( function ( $file ) use ( &$result ) {

      try {
       include $file ;
      } catch (\Throwable $ex) {
       echo "Ocorreu um erro ao carregar o arquivo <code>$file</code>." ;

       array_push ( $result , [
        "item" => $file ,
        "message" => "Ocorreu um erro ao carregar o arquivo <code>$file</code>." ,
        "details" => [
         "internal" => $ex->getMessage ( ) ,
         "file" => $ex->getFile ( ) ,
         "line" => $ex->getLine ( ) ,
         "trace" => $ex->getTraceAsString ( ) ,
        ]
       ] ) ;

      }

     } , $files ) ;

    }

   
   /* Se o $item de $list não for um diretório . . . */
    
    if ( !is_dir ( $item ) ) {
    
     $file = $item ;

     // Usa 3 condições para verificar se `$file` é válido:
      if ( str_starts_with ( $file , "." ) ) { 
       return ;
      }

      if ( !file_exists ( $file ) ) {
       $ex = new Error ( "O arquivo <code>$file</code> não existe." ) ;

       array_push ( $result , [
        "item" => $file ,
        "message" => "O arquivo $file não existe." ,
        "details" => [
         "internal" => $ex->getMessage ( ) ,
         "file" => $ex->getFile ( ) ,
         "line" => $ex->getLine ( ) ,
         "trace" => $ex->getTraceAsString ( ) ,
        ]
       ] ) ;

       return ;
      }

      if ( !str_ends_with ( $file , ".php" ) ) {
       $ex = new Error ( "O arquivo <code>$file</code> é inválido." ) ;

       array_push ( $result , [
        "item" => $file ,
        "message" => "O arquivo '$file' não parece ser um script php válido porque não 
          possui a extensão 'php'. Apenas arquivos '.php' são permitidos." ,
        "details" => [
         "internal" => $ex->getMessage ( ) ,
         "file" => $ex->getFile ( ) ,
         "line" => $ex->getLine ( ) ,
         "trace" => $ex->getTraceAsString ( ) ,
        ]
       ] ) ;

       return ;
      }

     // Realiza a inclusão se o `$file` cumprir os requisitos
      try {
       include $file ;
      } catch (\Throwable $ex) {
       echo "Ocorreu um erro ao carregar o arquivo <code>$file</code>." ;

       array_push ( $result , [
        "item" => $file ,
        "message" => "Ocorreu um erro ao carregar o arquivo <code>$file</code>." ,
        "details" => [
         "internal" => $ex->getMessage ( ) ,
         "file" => $ex->getFile ( ) ,
         "line" => $ex->getLine ( ) ,
         "trace" => $ex->getTraceAsString ( ) ,
        ]
       ] ) ;

      }

    }

  } , $list ) ;

  $count_errors = count ( $result ) ;
  
  if ( $count_errors > 0 ) {
   echo ( $count_errors == 1 ) ? (
    "A inclusão foi concluída, mas foi identificado " . count ( $result ) . " erro no processo."
   ) : (
    "A inclusão foi concluída, mas foram identificados " . count ( $result ) . " erros no processo."
   ) ;
  }
  
  return $result ;
   
 }

 throw new InvalidArgumentException ( "Há algo errado nos parâmetros da função <code>import ( )</code>." ) ;

}