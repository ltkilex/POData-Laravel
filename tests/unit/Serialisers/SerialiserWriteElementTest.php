<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\SimpleDataService as DataService;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class SerialiserWriteElementTest extends SerialiserTestBase
{
    public function testCompareWriteSingleModelWithPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($meta, null);
        $testModel->id = 1;
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestModel();
        $model->id = 4;
        $objectResult = $object->writeTopLevelElement($model);
        $ironicResult = $ironic->writeTopLevelElement($model);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $objectResult->propertyContent = new ODataPropertyContent();
        $ironicResult->propertyContent = new ODataPropertyContent();
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteSingleModelWithPropertiesNulledAndSingleRelation()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicSources');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestMonomorphicSources');

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $source = new TestMonomorphicSource($meta, null);
        $target = new TestMonomorphicTarget($meta, null);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestMonomorphicSource();
        $model->id = 42;
        $objectResult = $object->writeTopLevelElement($model);
        $ironicResult = $ironic->writeTopLevelElement($model);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $objectResult->propertyContent = new ODataPropertyContent();
        $ironicResult->propertyContent = new ODataPropertyContent();
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareSingleModelAtSingletonEndOfRelation()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicTargets');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestMonomorphicTargets');

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $source = new TestMonomorphicSource($meta, null);
        $target = new TestMonomorphicTarget($meta, null);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestMonomorphicTarget();
        $model->id = 42;
        $objectResult = $object->writeTopLevelElement($model);
        $ironicResult = $ironic->writeTopLevelElement($model);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $objectResult->propertyContent = new ODataPropertyContent();
        $ironicResult->propertyContent = new ODataPropertyContent();
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareSingleModelWithTwoExpandedProperties()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/TestMonomorphicSources(id=42)?$expand=oneSource,manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/TestMonomorphicSources(id=42)?$expand=oneSource,manySource');
        $request->request = new ParameterBag([ '$expand' => "oneSource,manySource"]);

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $source = new TestMonomorphicSource($metadata, null);
        $target = new TestMonomorphicTarget($metadata, null);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('oneSource');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, false);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $request = $processor->getRequest();
        $request->setRootProjectionNode($node);

        $object = new ObjectModelSerializer($service, $request);
        $ironic = new IronicSerialiser($service, $request);

        $belongsTo = m::mock(BelongsTo::class)->makePartial();
        $belongsTo->shouldReceive('getResults')->andReturn(null);
        $targ = m::mock(TestMonomorphicTarget::class)->makePartial();
        $targ->shouldReceive('metadata')->andReturn($metadata);
        $targ->shouldReceive('manyTarget')->andReturn($belongsTo);
        $targ->shouldReceive('oneTarget')->andReturn($belongsTo);
        $targ->id = 11;

        $hasOne = m::mock(HasMany::class)->makePartial();
        $hasOne->shouldReceive('getResults')->andReturn($targ);

        $hasMany = m::mock(HasMany::class)->makePartial();
        $hasMany->shouldReceive('getResults')->andReturn([$targ]);

        $model = m::mock(TestMonomorphicSource::class)->makePartial();
        $model->shouldReceive('hasOne')->andReturn($hasOne);
        $model->shouldReceive('manySource')->andReturn($hasMany);
        $model->shouldReceive('metadata')->andReturn($metadata);
        $model->id = 42;

        $objectResult = $object->writeTopLevelElement($model);

        // check that object result is properly set up - if not, no point comparing it to anything
        $this->assertTrue($objectResult->links[0]->isExpanded);
        $this->assertFalse($objectResult->links[0]->isCollection);
        $this->assertTrue($objectResult->links[1]->isExpanded);
        $this->assertTrue($objectResult->links[1]->isCollection);
        $ironicResult = $ironic->writeTopLevelElement($model);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $objectResult->propertyContent = new ODataPropertyContent();
        $ironicResult->propertyContent = new ODataPropertyContent();
        foreach ($objectResult->links as $link) {
            if (!isset($link->expandedResult)) {
                continue;
            }
            $link->expandedResult->propertyContent = new ODataPropertyContent();
        }
        foreach ($ironicResult->links as $link) {
            if (!isset($link->expandedResult)) {
                continue;
            }
            $link->expandedResult->propertyContent = new ODataPropertyContent();
        }
        $this->assertEquals($objectResult, $ironicResult);
    }
}
