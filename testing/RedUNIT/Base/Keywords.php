<?php
/**
 * RedUNIT_Base_Keywords 
 * @file 			RedUNIT/Base/Keywords.php
 * @description		Tests for possible keyword clashes.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Keywords extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
	
		$keywords = array('anokeyword','znokeyword','group','DROP','inner','JOIN','select',
		'table','int','cascade','float','CALL','in','status','order',
		'limit','having','else','if','while','distinct','like');
		
		$counter = 0;
		
		foreach($keywords as $k) {
			R::nuke();
			$bean = R::dispense($k);
			$bean->$k = $k;
			$id = R::store($bean);
			$bean = R::load($k,$id);
			$bean2 = R::dispense('other');
			$bean2->name = $k;
			$bean->bean = $bean2;
			$bean->ownBean[] = $bean2;
			$bean->sharedBean[] = $bean2;
			$id = R::store($bean);
			
			R::view('perspective',$k.',other');
			$e = new RedBean_Plugin_BeanExport(R::$toolbox);
			$e->loadSchema();
			$s=((unserialize($e->getSchema())));
			ksort($s);
			$s = json_encode($s);
			asrt((strlen($s)>20),true);
			
			if (!$counter) $refs1 = $s; 
			if ($counter==1) $refs2 = $s;
			
			if (str_replace('anokeyword',$k,$refs1)===$s || str_replace('znokeyword',$k,$refs2)===$s) pass(); else fail(); 
			$counter++;
			R::trash($bean);
				
		}
	}
}