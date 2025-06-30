import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="PrevidIA - Sistema Inteligente de Gestão Previdenciária">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <Link
                                href={route('login')}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Entrar
                            </Link>
                        )}
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <div className="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                            <div className="mb-4">
                                <h1 className="mb-2 text-2xl font-bold text-blue-600 dark:text-blue-400">PrevidIA</h1>
                                <p className="text-lg font-medium text-gray-800 dark:text-gray-200">Sistema Inteligente de Gestão Previdenciária</p>
                            </div>
                            <h2 className="mb-1 font-medium">Bem-vindo ao futuro da advocacia previdenciária</h2>
                            <p className="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                                O PrevidIA revoluciona a gestão de processos previdenciários com inteligência artificial.
                                <br />
                                Comece a explorar nossas principais funcionalidades.
                            </p>
                            <ul className="mb-4 flex flex-col lg:mb-6">
                                <li className="relative flex items-center gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-blue-500" />
                                        </span>
                                    </span>
                                    <span>
                                        <strong>Gestão Inteligente de Processos:</strong> Organize e acompanhe todos os seus processos previdenciários
                                        em um só lugar
                                    </span>
                                </li>
                                <li className="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-green-500" />
                                        </span>
                                    </span>
                                    <span>
                                        <strong>Análise Automática de Documentos:</strong> Extraia informações do CNIS e outros documentos
                                        automaticamente
                                    </span>
                                </li>
                                <li className="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-purple-500" />
                                        </span>
                                    </span>
                                    <span>
                                        <strong>Assistente de IA:</strong> Chat inteligente para tirar dúvidas sobre direito previdenciário
                                    </span>
                                </li>
                                <li className="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-orange-500" />
                                        </span>
                                    </span>
                                    <span>
                                        <strong>Templates de Petições:</strong> Modelos personalizáveis para agilizar a criação de documentos
                                        jurídicos
                                    </span>
                                </li>
                            </ul>
                            <div className="flex gap-3 text-sm leading-normal">
                                {!auth.user && (
                                    <a
                                        href="https://wa.me/5549991677823"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 rounded-sm border border-green-600 bg-green-600 px-5 py-1.5 text-sm leading-normal text-white hover:border-green-700 hover:bg-green-700 dark:border-green-500 dark:bg-green-500 dark:hover:border-green-400 dark:hover:bg-green-400"
                                    >
                                        <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                                        </svg>
                                        Fale Conosco
                                    </a>
                                )}
                                {auth.user && (
                                    <Link
                                        href={route('dashboard')}
                                        className="inline-block rounded-sm border border-blue-600 bg-blue-600 px-5 py-1.5 text-sm leading-normal text-white hover:border-blue-700 hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:border-blue-400 dark:hover:bg-blue-400"
                                    >
                                        Acessar Dashboard
                                    </Link>
                                )}
                                <span className="inline-block rounded-sm border border-gray-300 bg-gray-100 px-5 py-1.5 text-sm leading-normal text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    Versão Beta
                                </span>
                            </div>
                        </div>
                        <div className="relative -mb-px aspect-[335/376] w-full shrink-0 overflow-hidden rounded-t-lg bg-gradient-to-br from-blue-50 to-indigo-100 lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:from-blue-950 dark:to-indigo-950">
                            {/* Logo PrevidIA */}
                            <div className="flex h-full items-center justify-center p-8">
                                <div className="text-center">
                                    <div className="mb-6 text-6xl font-bold text-blue-600 dark:text-blue-400">PrevidIA</div>
                                    <div className="mb-4 text-xl text-gray-700 dark:text-gray-300">Inteligência Artificial</div>
                                    <div className="text-lg text-gray-600 dark:text-gray-400">para Advocacia Previdenciária</div>
                                    <div className="mt-8 flex justify-center">
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div className="flex items-center gap-2 text-green-600 dark:text-green-400">
                                                <div className="h-2 w-2 rounded-full bg-green-500"></div>
                                                <span>IA Avançada</span>
                                            </div>
                                            <div className="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                                <div className="h-2 w-2 rounded-full bg-blue-500"></div>
                                                <span>Automação</span>
                                            </div>
                                            <div className="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                                                <div className="h-2 w-2 rounded-full bg-purple-500"></div>
                                                <span>Eficiência</span>
                                            </div>
                                            <div className="flex items-center gap-2 text-orange-600 dark:text-orange-400">
                                                <div className="h-2 w-2 rounded-full bg-orange-500"></div>
                                                <span>Precisão</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
