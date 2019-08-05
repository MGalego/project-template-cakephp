<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ScheduledPersonalData Model
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\RecordsTable|\Cake\ORM\Association\BelongsTo $Records
 *
 * @method \App\Model\Entity\ScheduledPersonalData get($primaryKey, $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\ScheduledPersonalData findOrCreate($search, callable $callback = null, $options = [])
 */
class ScheduledPersonalDataTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('scheduled_personal_data');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->dateTime('scheduled')
            ->requirePresence('scheduled', 'create')
            ->notEmpty('scheduled');

        $validator
            ->scalar('status')
            ->maxLength('status', 255)
            ->requirePresence('status', 'create')
            ->notEmpty('status');

        $validator
            ->scalar('errors')
            ->maxLength('errors', 255)
            ->allowEmpty('errors');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }
}
