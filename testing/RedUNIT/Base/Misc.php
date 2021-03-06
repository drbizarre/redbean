<?php
/**
 * RedUNIT_Base_Misc 
 * @file 			RedUNIT/Base/Misc.php
 * @description		Various tests.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Misc extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		global $currentDriver; 
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		$painting = R::dispense('painting');
		$painting->name = 'Nighthawks';
		$id=R::store($painting);
		
		$fHelper = new RedBean_FacadeHelper($currentDriver);
		asrt($fHelper->dispense('bean') instanceof RedBean_OODBBean,true);
		$found =$fHelper->find('painting',' name = ? ', array('Nighthawks'));
		asrt(count($found),1);
		
		asrt($fHelper->loadOrDispense('painting',$id+1) instanceof RedBean_OODBBean,true);
		asrt($fHelper->loadOrDispense('painting',$id) instanceof RedBean_OODBBean,true);
		asrt($fHelper->loadOrDispense('painting',$id)->name,'Nighthawks');
		
		
		$cooker = new RedBean_Cooker();
		$cooker->setToolbox($toolbox);
		asrt($cooker->graph('abc'),'abc');
		
		foreach($writer->typeno_sqltype as $code=>$text) {
			asrt(is_integer($code),true);
			asrt(is_string($text),true);
		}
		foreach($writer->sqltype_typeno as $text=>$code) {
			asrt(is_integer($code),true);
			asrt(is_string($text),true);
		}
		
		R::exec('select * from nowhere');
		pass();
		R::getAll('select * from nowhere');
		pass();
		R::getAssoc('select * from nowhere');
		pass();
		R::getCol('select * from nowhere');
		pass();
		R::getCell('select * from nowhere');
		pass();
		R::getRow('select * from nowhere');
		pass();
		
		R::freeze(true);
		try{ R::exec('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getAll('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getCell('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getAssoc('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getRow('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getCol('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		R::freeze(false);
		
		
		R::nuke();
		$bean=$redbean->dispense('bean');
		$bean->prop = 1;
		$redbean->store($bean);
		$adapter->exec('UPDATE bean SET prop = 2');
		asrt($adapter->getAffectedRows(),1);
		asrt($adapter->getDatabase()->getPDO() instanceof PDO, true);
		
		asrt(strlen($adapter->getDatabase()->getDatabaseVersion())>0,true);
		asrt(strlen($adapter->getDatabase()->getDatabaseType())>0,true);
		 
		
		R::nuke();
		$track = R::dispense('track');
		$album = R::dispense('cd');
		$track->name = 'a';
		$track->orderNum = 1;
		$track2 = R::dispense('track');
		$track2->orderNum = 2;
		$track2->name = 'b';
		R::associate( $album, $track );
		R::associate( $album, $track2 );
		$tracks = R::related( $album, 'track');
		$track = array_shift($tracks);
		$track2 = array_shift($tracks);
		$ab = $track->name.$track2->name;
		asrt(($ab=='ab' || $ab=='ba'),true);
		
		$t = R::dispense('person');
		$s = R::dispense('person');
		$s2 = R::dispense('person');
		$t->name = 'a';
		$t->role = 'teacher';
		$s->role = 'student';
		$s2->role = 'student';
		$s->name = 'a';
		$s2->name = 'b';
		R::associate($t, $s);
		R::associate($t, $s2);
		$students = R::related($t, 'person', ' role = ?  ',array("student"));
		$s = array_shift($students);
		$s2 = array_shift($students);
		asrt(($s->name=='a' || $s2->name=='a'),true);
		asrt(($s->name=='b' || $s2->name=='b'),true);
		$s= R::relatedOne($t, 'person', ' role = ?  ',array("student"));
		asrt($s->name,'a');
		//empty classroom
		R::clearRelations($t, 'person', $s2);
		$students = R::related($t, 'person', ' role = ?  ',array("student"));
		asrt(count($students),1);
		$s = reset($students);
		asrt($s->name, 'b');

		testpack('transactions');
		R::nuke();
		R::begin();
		$bean = R::dispense('bean');
		R::store($bean);
		R::commit();
		asrt(R::count('bean'),1);
		R::wipe('bean');
		R::freeze(1);
		R::begin();
		$bean = R::dispense('bean');
		R::store($bean);
		R::rollback();
		asrt(R::count('bean'),0);
		R::freeze(false);
		
		
		testpack('genSlots');
		asrt(R::genSlots(array('a','b')),'?,?');				
		asrt(R::genSlots(array('a')),'?');
		asrt(R::genSlots(array()),'');
		
		
		
				
		testpack('FUSE models cant touch nested beans in update() - issue 106');
		R::nuke();
		
		$spoon = R::dispense('spoon');
		$spoon->name = 'spoon for test bean';
		$deep = R::dispense('deep');
		$deep->name = 'deepbean';
		$item = R::dispense('item');
		$item->val = 'Test';
		$item->deep = $deep;
		
		$test = R::dispense('test');
		$test->item = $item;
		$test->sharedSpoon[] = $spoon;
		
		
		$test->isNowTainted = true;
		$id=R::store($test); 
		$test = R::load('test',$id);
		asrt($test->item->val,'Test2');
		$can = reset($test->ownCan);
		$spoon = reset($test->sharedSpoon);
		asrt($can->name,'can for bean');
		asrt($spoon->name,'S2');
		asrt($test->item->deep->name,'123');
		asrt(count($test->ownCan),1);
		asrt(count($test->sharedSpoon),1);
		asrt(count($test->sharedPeas),10);
		asrt(count($test->ownChip),9);
		
		R::nuke();
		$coffee = R::dispense('coffee');
		$coffee->size = 'XL';
		$coffee->ownSugar = R::dispense('sugar',5);
		
		$id = R::store($coffee);
		
		
		$coffee=R::load('coffee',$id);
		asrt(count($coffee->ownSugar),3);
		$coffee->ownSugar = R::dispense('sugar',2);
		$id = R::store($coffee);
		$coffee=R::load('coffee',$id);
		asrt(count($coffee->ownSugar),2);
		
		
		
		$cocoa = R::dispense('cocoa');
		$cocoa->name = 'Fair Cocoa';
		list($taste1,$taste2) = R::dispense('taste',2);
		$taste1->name = 'sweet';
		$taste2->name = 'bitter';
		$cocoa->ownTaste = array($taste1, $taste2);
		R::store($cocoa);
		
		$cocoa->name = 'Koko';
		R::store($cocoa);
		
		$pdo = R::$adapter->getDatabase()->getPDO();
		$driver = new RedBean_Driver_PDO($pdo);
		pass();
		asrt($pdo->getAttribute(PDO::ATTR_ERRMODE), PDO::ERRMODE_EXCEPTION);
		asrt($pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE), PDO::FETCH_ASSOC);
		asrt(strval($driver->GetCell('select 123')),'123');
	
		$a = new RedBean_Exception_SQL;
		$a->setSqlState('test');
		$b = strval($a);
		asrt($b,'[test] - ');
	}

}



