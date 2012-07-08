<?php
return array(
		'comments' => array(
				'referenceMap' => array(
						'ownerUser' => array(
								Db_Table::COLUMNS => 'user_id',
								Db_Table::REF_COLUMNS => 'id',
								Db_Table::REF_TABLE => 'users'
						)
				)
		)
);