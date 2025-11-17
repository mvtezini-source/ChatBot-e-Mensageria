import React, {useEffect, useState, useRef} from 'react';
import useWebSocket from '../hooks/useWebSocket';
import axios from 'axios';

export default function ChatWindow({user, conversationId, token}){
  const [messages, setMessages] = useState([]);
  const wsRef = useWebSocket('ws://localhost:8080/?token='+token, (data)=>{
    if (!data) return;
    if (data.conversation_id == conversationId) setMessages(m=>[...m,{from:data.from,content:data.content,system:false}]);
  });
  const inputRef = useRef();

  useEffect(()=>{
    // Load history via REST
    axios.get('http://localhost:8000/api/conversations/'+conversationId+'/messages',{headers:{Authorization:'Bearer '+token}})
      .then(r=>setMessages(r.data))
      .catch(()=>{});
  },[conversationId, token]);

  // subscribe to conversation on WS when ready
  useEffect(()=>{
    const trySubscribe = ()=>{
      const ws = wsRef.current;
      if (ws && ws.readyState === 1){
        ws.send(JSON.stringify({type:'subscribe',conversation_id:conversationId}));
      }
    };
    // try immediately and again after short delay (ws may still be opening)
    trySubscribe();
    const t = setTimeout(trySubscribe, 500);
    return ()=>clearTimeout(t);
  },[conversationId, wsRef]);

  const send = async ()=>{
    const content = inputRef.current.value;
    if (!content) return;
    // Persist via REST
    try{
      await axios.post('http://localhost:8000/api/messages',{conversation_id:conversationId,content},{headers:{Authorization:'Bearer '+token}});
    }catch(e){ console.error(e); }
    // Send via WS for real-time
    const ws = wsRef.current;
    const payload = {type:'message',conversation_id:conversationId,from:user.name,content};
    try{
      ws && ws.send(JSON.stringify(payload));
    }catch(e){ console.error(e); }
    setMessages(m=>[...m,{from:user.name,content}]);
    inputRef.current.value = '';
  };

  return (
    <div style={{padding:16}}>
      <h3>Chat #{conversationId}</h3>
      <div style={{height:'70vh',overflow:'auto',border:'1px solid #eee',padding:8}}>
        {messages.map((m,i)=>(<div key={i}><b>{m.from}</b>: {m.content}</div>))}
      </div>
      <div style={{marginTop:8}}>
        <input ref={inputRef} style={{width:'80%'}} />
        <button onClick={send}>Enviar</button>
      </div>
    </div>
  );
}
