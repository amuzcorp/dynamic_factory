<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Illuminate\Http\Request;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Counter\Counter;
use Xpressengine\Database\VirtualConnectionInterface as VirtualConnection;
use Xpressengine\Document\ConfigHandler;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Document\InstanceManager;
use Xpressengine\Storage\Storage;
use Xpressengine\User\UserInterface;

class DynamicFactoryDocumentHandler
{
    /**
     * @var DocumentHandler
     */
    protected $documentHandler;

    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var Counter
     */
    protected $readCounter;

    /**
     * @var Counter
     */
    protected $voteCounter;


    public function __construct(
        DocumentHandler $documentHandler,
        Storage $storage,
        Counter $readCounter,
        Counter $voteCounter
    )
    {
        $this->documentHandler = $documentHandler;
        $this->storage = $storage;
        $this->readCounter = $readCounter;
        $this->voteCounter = $voteCounter;
    }

    public function store($attributes)
    {
        $cpt_id = $attributes['cpt_id'];

        $attributes['instance_id'] = $cpt_id;
        $attributes['type'] = $cpt_id;

        return $this->documentHandler->add($attributes);
    }

    public function update($doc, $inputs)
    {
        $attributes = $doc->getAttributes();

        foreach ($inputs as $name => $value) {
            if (array_key_exists($name, $attributes)) {
                $doc->{$name} = $value;
            }
        }

        return $this->documentHandler->put($doc);
    }

    public function incrementReadCount(CptDocument $doc, UserInterface $user)
    {
        if ($this->readCounter->has($doc->id, $user) === false) {
            $this->readCounter->add($doc->id, $user);
        }

        $doc->read_count = $this->readCounter->getPoint($doc->id);
        $doc->timestamps = false;
        $doc->save();
    }
}
