import { useEffect, useRef } from 'react';

export default function useWebSocket(url, token, onMessage){
  const wsRef = useRef(null);

  useEffect(()=>{
    let ws;
    try{
      // Use token as subprotocol for secure passing from browser
      ws = token ? new WebSocket(url, token) : new WebSocket(url);
    }catch(e){
      console.error('ws ctor error', e);
      return;
    }
    ws.onopen = ()=>console.log('ws open');
    ws.onmessage = (ev)=>{
      let data = null;
      try { data = JSON.parse(ev.data); } catch(e){}
      onMessage && onMessage(data);
    };
    ws.onerror = (e)=>console.error('ws error',e);
    ws.onclose = ()=>console.log('ws closed');
    wsRef.current = ws;
    return ()=>{ ws.close(); };
  },[url, token]);

  return wsRef;
}
