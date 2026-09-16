<?php

namespace Motor\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motor\Admin\Models\Language;

class LanguageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Language::class;

    /**
     * Laufender Zaehler fuer iso_639_1, siehe nextIsoCode().
     */
    protected static int $isoSequence = 0;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'iso_639_1'    => $this->nextIsoCode(),
            'english_name' => $this->faker->word(),
            'native_name'  => $this->faker->word(),
        ];
    }

    /**
     * Erzeugt einen garantiert freien zweistelligen Sprachcode.
     *
     * Zuvor stand hier Str::random(2). Seit dem 20.04.2026 traegt iso_639_1
     * einen UNIQUE-Index; die Factory wurde damals nicht nachgezogen. Zwei
     * Aufrufe im selben Test konnten deshalb denselben Code ziehen und der
     * zweite lief in eine UniqueConstraintViolationException. Am 25.08. hat
     * das die naechtliche Pipeline gekippt: 743 Tests gruen, einer rot, weil
     * zweimal "en" gezogen wurde.
     *
     * Zwei Eigenschaften schliessen beide Kollisionsarten aus:
     * - Der Zaehler statt Zufall verhindert, dass sich zwei Aufrufe im selben
     *   Lauf treffen.
     * - Das erste Zeichen ist immer eine Ziffer. Echte ISO-639-1-Codes
     *   bestehen aus zwei Kleinbuchstaben, also kann kein erzeugter Code mit
     *   einem gesetzten oder migrierten Code kollidieren.
     *
     * Ergibt 360 verschiedene Codes - deutlich mehr, als ein Testlauf braucht.
     */
    protected function nextIsoCode(): string
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';
        $n = static::$isoSequence++;

        return $alphabet[$n % 10].$alphabet[intdiv($n, 10) % 36];
    }
}
