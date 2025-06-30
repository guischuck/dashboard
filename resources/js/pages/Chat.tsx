import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import React, { useEffect, useRef, useState } from 'react';

interface Client {
    id: number;
    name: string;
}

interface Message {
    sender: 'user' | 'chatgpt';
    text: string;
}

// Função para converter markdown básico em HTML
const parseMarkdown = (text: string): string => {
    return (
        text
            // Negrito: **texto** -> <strong>texto</strong>
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Itálico: *texto* -> <em>texto</em>
            .replace(/(?<!\*)\*([^*]+?)\*(?!\*)/g, '<em>$1</em>')
            // Lista com - no início da linha
            .replace(/^- (.+)$/gm, '<li class="ml-4 list-disc">$1</li>')
            // Lista numerada
            .replace(/^\d+\. (.+)$/gm, '<li class="ml-4 list-decimal">$1</li>')
            // Envolver listas consecutivas em <ul>
            .replace(/((?:<li class="ml-4 list-disc">.*?<\/li>\s*)+)/gs, '<ul class="list-disc ml-4 space-y-1">$1</ul>')
            // Envolver listas numeradas consecutivas em <ol>
            .replace(/((?:<li class="ml-4 list-decimal">.*?<\/li>\s*)+)/gs, '<ol class="list-decimal ml-4 space-y-1">$1</ol>')
            // Quebras de linha duplas -> parágrafos
            .replace(/\n\n/g, '</p><p class="mb-2">')
            // Quebras de linha simples -> <br>
            .replace(/\n/g, '<br />')
            // Envolver em parágrafo se não começar com tag HTML
            .replace(/^(?!<[uo]l|<li|<p)/gm, '<p class="mb-2">')
            // Fechar parágrafo no final se necessário
            .replace(/([^>])$/g, '$1</p>')
    );
};

export default function Chat() {
    const [clients, setClients] = useState<Client[]>([]);
    const [clientSearch, setClientSearch] = useState('');
    const [selectedClient, setSelectedClient] = useState<Client | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    // Buscar clientes reais
    useEffect(() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('/api/clients', {
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || '',
            },
        })
            .then((res) => {
                if (!res.ok) {
                    throw new Error('Erro ao buscar clientes');
                }
                return res.json();
            })
            .then((data) => {
                console.log('Clientes carregados:', data);
                setClients(data);
            })
            .catch((error) => {
                console.error('Erro ao carregar clientes:', error);
                setClients([]);
            });
    }, []);

    // Filtrar clientes baseado na busca
    const filteredClients = (() => {
        if (!clientSearch.trim()) return [];
        const searchTerm = clientSearch.toLowerCase().trim();
        return clients.filter((client) => {
            const clientName = (client.name || '').toLowerCase();
            return clientName.includes(searchTerm);
        });
    })();

    // Scroll automático para a última mensagem
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendMessage = async () => {
        if (!input.trim()) return;

        setMessages((prev) => [...prev, { sender: 'user', text: input }]);
        const currentInput = input;
        setInput('');
        setLoading(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const res = await fetch('/api/ai-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                credentials: 'include',
                body: JSON.stringify({
                    client_id: selectedClient?.id || null,
                    message: currentInput,
                }),
            });

            if (!res.ok) {
                throw new Error(`Erro HTTP: ${res.status}`);
            }

            const data = await res.json();

            if (data.success) {
                setMessages((prev) => [...prev, { sender: 'chatgpt', text: data.response || 'Sem resposta.' }]);
            } else {
                setMessages((prev) => [...prev, { sender: 'chatgpt', text: data.response || 'Erro ao processar mensagem.' }]);
            }
        } catch (e) {
            console.error('Erro no chat:', e);
            setMessages((prev) => [
                ...prev,
                { sender: 'chatgpt', text: 'Erro ao conectar com o servidor. Verifique sua conexão e tente novamente.' },
            ]);
        }
        setLoading(false);
    };

    const handleInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') sendMessage();
    };

    // Resetar chat ao trocar de cliente
    useEffect(() => {
        setMessages([]);
        setInput('');
        inputRef.current?.focus();
    }, [selectedClient]);

    return (
        <AppLayout>
            <Head title="Chat com ChatGPT" />
            <div className="flex h-full w-full flex-col overflow-hidden bg-background">
                {/* Topo: seleção de cliente */}
                <div className="flex flex-col gap-2 border-b bg-muted/50 px-2 py-3 md:px-6 md:py-4">
                    <div className="text-lg font-semibold text-foreground">Chat com ChatGPT</div>
                    {!selectedClient ? (
                        <div className="relative">
                            <Input
                                placeholder="Busque o cliente pelo nome..."
                                value={clientSearch}
                                onChange={(e) => setClientSearch(e.target.value)}
                                className="pr-2"
                                autoFocus
                            />
                            {/* Dropdown de clientes */}
                            {clientSearch.trim() && filteredClients.length > 0 && (
                                <div className="absolute top-full right-0 left-0 z-10 max-h-48 overflow-y-auto rounded-md border bg-background shadow-lg">
                                    {filteredClients.map((c) => (
                                        <button
                                            key={c.id}
                                            className="w-full border-b px-3 py-2 text-left text-foreground last:border-b-0 hover:bg-muted"
                                            onClick={() => {
                                                setSelectedClient(c);
                                                setClientSearch('');
                                            }}
                                        >
                                            {c.name}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="flex items-center gap-2">
                            <span className="font-medium text-blue-600 dark:text-blue-400">{selectedClient.name}</span>
                            <Button size="sm" variant="outline" onClick={() => setSelectedClient(null)}>
                                Trocar cliente
                            </Button>
                        </div>
                    )}
                </div>
                {/* Área de mensagens */}
                <div className="flex min-h-[300px] flex-1 flex-col overflow-y-auto bg-muted/30 px-1 py-2 md:px-6 md:py-4">
                    {messages.length === 0 ? (
                        <div className="flex min-h-[60vh] w-full flex-1 items-center justify-center">
                            <div className="mx-2 flex w-full max-w-xl flex-col items-center gap-4 rounded-xl border bg-card p-4 shadow-lg md:p-8">
                                <div className="mb-2 rounded-full bg-blue-100 p-3 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24">
                                        <path
                                            fill="currentColor"
                                            d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 18.75A8.75 8.75 0 1 1 20.75 12 8.76 8.76 0 0 1 12 20.75Zm0-13.5a1.25 1.25 0 1 1-1.25 1.25A1.25 1.25 0 0 1 12 7.25Zm1.25 8.5a1.25 1.25 0 0 1-2.5 0v-3.5a1.25 1.25 0 0 1 2.5 0Z"
                                        />
                                    </svg>
                                </div>
                                <div className="text-center text-lg font-semibold text-card-foreground">
                                    Olá! Sou sua assistente previdenciária especializada. Posso ajudar com:
                                </div>
                                <ul className="mx-auto w-full max-w-xs list-disc space-y-1 pl-5 text-left text-base text-muted-foreground">
                                    <li>Análise de casos previdenciários</li>
                                    <li>Interpretação de documentos CNIS</li>
                                    <li>Estratégias para benefícios</li>
                                    <li>Dúvidas sobre vínculos empregatícios</li>
                                    <li>Orientações sobre coleta de documentos</li>
                                </ul>
                                <div className="mt-2 w-full text-center text-sm text-muted-foreground">
                                    <b>💡 Dica:</b> Use a busca acima para selecionar um cliente e obter respostas mais precisas!
                                </div>
                            </div>
                        </div>
                    ) : (
                        messages.map((msg, idx) => (
                            <div key={idx} className={`flex ${msg.sender === 'user' ? 'justify-end' : 'justify-start'} mb-2 w-full`}>
                                <div
                                    className={`max-w-[95vw] rounded-xl px-4 py-2 shadow-sm md:max-w-[75%] ${
                                        msg.sender === 'user'
                                            ? 'rounded-br-none bg-blue-600 text-white'
                                            : 'rounded-bl-none border bg-card text-card-foreground'
                                    }`}
                                >
                                    {msg.sender === 'chatgpt' ? (
                                        <div
                                            className="markdown-content text-gray-900 dark:text-gray-100 [&_em]:italic [&_li]:ml-4 [&_li]:text-gray-900 [&_li]:dark:text-gray-100 [&_ol]:list-decimal [&_p]:mb-2 [&_p]:text-gray-900 [&_p]:dark:text-gray-100 [&_strong]:font-semibold [&_ul]:list-disc"
                                            dangerouslySetInnerHTML={{
                                                __html: parseMarkdown(msg.text),
                                            }}
                                        />
                                    ) : (
                                        <div className="whitespace-pre-wrap">{msg.text}</div>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                    <div ref={messagesEndRef} />
                </div>
                {/* Input fixo */}
                <div className="flex gap-2 border-t bg-background px-1 py-3 md:px-6 md:py-4">
                    <Input
                        ref={inputRef}
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={handleInputKeyDown}
                        placeholder="Digite sua mensagem..."
                        disabled={loading}
                        className="min-w-0 flex-1"
                        autoFocus
                    />
                    <Button onClick={sendMessage} disabled={!input.trim() || loading} className="shrink-0">
                        {loading ? 'Enviando...' : 'Enviar'}
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
