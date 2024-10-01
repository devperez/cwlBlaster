<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    {{-- <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div> --}}

    <div>
        <p>Connexion discogs</p>
        <button type="button" id="discogsConnection">Connexion</button>        
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            let button = document.getElementById('discogsConnection');
            button.addEventListener('click', function(){
                fetch('/discogs/connexion')
                    .then(response => {
                        if(!response.ok) {
                            throw new Error('Erreur lors de la connexion:' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        window.location.href = data.authorizationUrl;
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                    });
            });
        });
    </script>
</x-app-layout>
