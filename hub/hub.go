package hub

import (
	"cbsignal/client"
	message "cbsignal/protobuf"
	"cbsignal/redis"
	"cbsignal/util/fastmap/cmap"
	"cbsignal/util/log"
	"github.com/bytedance/sonic"
	"github.com/golang/protobuf/proto"
	"sync"
	"time"
)

const (
	MQ_BLOCK_DURATION = 5 * time.Second
	CONSUME_THREADS   = 18
)

var (
	h    *Hub
	once sync.Once
)

type Hub struct {
	Clients cmap.ConcurrentMap
}

func Init(addr string) {
	once.Do(func() {
		h = &Hub{
			Clients: cmap.NewCMap(),
			//Clients: smap.NewSMap(),
		}

		for i := 0; i < CONSUME_THREADS; i++ {
			go Consume(addr)
		}

	})
}

func GetInstance() *Hub {
	return h
}

func GetClientCount() int64 {
	return int64(h.Clients.CountNoLock())
}

func GetClientNumPerMap() []int {
	return h.Clients.CountPerMapNoLock()
}

func DoRegister(client *client.Client) {
	log.Infof("hub DoRegister %s", client.PeerId)
	/*
		1. 本地保存节点id
		2. redis保存节点id和addr
	*/
	h.Clients.Set(client.PeerId, client)
	if err := redis.SetLocalPeer(client.PeerId); err != nil {
		log.Error(err)
	}
}

func GetClient(id string) (*client.Client, bool) {
	return h.Clients.Get(id)
}

func HasClient(id string) bool {
	return h.Clients.Has(id)
}

func RemoveClient(id string) {
	h.Clients.Remove(id)
}

func DoUnregister(peerId string) bool {
	log.Infof("hub DoUnregister %s", peerId)
	if peerId == "" {
		return false
	}
	/*
		1. 本地删除节点id
		2. redis删除节点id
	*/
	if h.Clients.Has(peerId) {
		h.Clients.Remove(peerId)
		//go func() {
		//	if err := redis.DelLocalPeer(peerId); err != nil {
		//		log.Error("DelLocalPeer", err)
		//	}
		//}()
		if err := redis.DelLocalPeer(peerId); err != nil {
			log.Error(err)
		}
		return true
	}
	return false
}

// send json object to a client with peerId
func SendJsonToClient(target *client.Client, value interface{}) (error, bool) {

	b, err := sonic.Marshal(value)
	if err != nil {
		log.Error(err)
		return err, false
	}
	defer func() { // 必须要先声明defer，否则不能捕获到panic异常
		if err := recover(); err != nil {
			log.Warnf(err.(string)) // 这里的err其实就是panic传入的内容
		}
	}()
	return target.SendMessage(b)
}

func ClearAll() {
	h.Clients.Clear()
}

func Consume(addr string) {
	defer func() {
		if err := recover(); err != nil {
			log.Warnf("Work failed with %s in %v", err)
			Consume(addr)
		}
	}()
	for {
		b, err := redis.BlockPopMQ(MQ_BLOCK_DURATION, addr)
		if err != nil {
			if err != redis.ERR_REDIS_NIL {
				log.Errorf("BlockPopMQ %s err %s", addr, err)
			}
			continue
		}
		go sendMessageToLocalPeer(b)
	}
}

func sendMessageToLocalPeer(raw []byte) {
	var data message.SignalBatchReq
	if err := proto.Unmarshal(raw, &data); err != nil {
		log.Error(err)
		return
	}
	for _, item := range data.Items {
		cli, ok := GetClient(item.ToPeerId)
		if ok {
			log.Infof("local peer %s found", item.ToPeerId)
			if err, _ := cli.SendMessage(item.Data); err != nil {
				log.Warnf("from remote send signal to peer %s error %s", item.ToPeerId, err)
				if ok := DoUnregister(cli.PeerId); ok {
					cli.Close()
				}
			}
		}
	}

}
