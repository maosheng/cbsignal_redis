package nodes

import (
	"cbsignal/util/log"
	"sync"
)

type NodeHub struct {
	nodes    map[string]*Node
	mu       sync.Mutex
	selfAddr string
}

var nodeHub *NodeHub

func NewNodeHub(selfAddr string) *NodeHub {
	n := NodeHub{
		nodes:    make(map[string]*Node, 20),
		selfAddr: selfAddr,
	}
	nodeHub = &n

	return &n
}

func GetNode(addr string) (*Node, bool) {
	return nodeHub.Get(addr)
}

func GetSelfAddr() string {
	return nodeHub.selfAddr
}

func GetTotalNumClient() int64 {
	var sum int64 = 0
	for _, node := range nodeHub.nodes {
		sum += node.NumClient
	}
	return sum
}

func GetNumNode() int {
	sum := 0
	for _, node := range nodeHub.nodes {
		if node.isAlive {
			sum += 1
		}
	}
	return sum
}

func (n *NodeHub) Delete(node *Node) {
	//n.mu.Lock()
	node.Destroy()
	log.Warnf("NodeHub delete %s", node.Addr())
	delete(n.nodes, node.Addr())
	//n.mu.Unlock()
}

func (n *NodeHub) Add(addr string, peer *Node) {
	log.Infof("NodeHub add %s", addr)
	n.nodes[addr] = peer
}

func (n *NodeHub) Get(addr string) (*Node, bool) {
	var err error
	node, ok := n.nodes[addr]
	if !ok {
		n.mu.Lock()
		defer n.mu.Unlock()
		node, ok = n.nodes[addr]
		if ok {
			return node, ok
		}
		log.Infof("New Node %s", addr)
		node, err = NewNode(addr)
		if err != nil {
			log.Error(err)
			return nil, false
		}
		ok = true
		n.Add(addr, node)
		node.StartHeartbeat()
	} else {
		if node.IsDead {
			n.Delete(node)
			return nil, false
		}
	}
	return node, ok
}

func (n *NodeHub) GetAll() map[string]*Node {
	//log.Infof("NodeHub GetAll %d", len(n.node))
	return n.nodes
}

func (n *NodeHub) Clear() {
	log.Info("NodeHub clear")
	//n.mu.Lock()

	n.nodes = make(map[string]*Node, 20)
	//n.mu.Unlock()
}

func ClearNodeHub() {
	nodeHub.Clear()
}
