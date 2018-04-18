<?php

namespace Orca\UserLogBundle\Twig\Extension;


class VarsExtension extends \Twig_Extension {


   public function getFilters()
   {
       return [
           new \Twig_SimpleFilter('decode', [$this, 'jsonDecode'])
       ];
   }

   public function jsonDecode($str) {

       $string = json_decode($str,true);
       return $string;
   }

}