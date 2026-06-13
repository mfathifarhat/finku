<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MascotShopController extends Controller
{
    /**
     * Buy an accessory for the mascot.
     */
    public function buy(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
            'price' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        if ($user->buyAccessory($request->code, $request->price)) {
            return redirect()->back()->with('success', "Aksesoris berhasil dibeli!");
        }

        return redirect()->back()->with('error', "Pembelian gagal! Finku Coins kamu tidak mencukupi.");
    }

    /**
     * Equip or unequip an accessory.
     */
    public function equip(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
            'slot' => 'required|string|in:hat,glasses,neck,back',
            'action' => 'required|string|in:equip,unequip',
        ]);

        $user = $request->user();

        if ($request->action === 'equip') {
            if ($user->equipAccessory($request->code, $request->slot)) {
                return redirect()->back()->with('success', "Aksesoris berhasil digunakan!");
            }
            return redirect()->back()->with('error', "Gagal menggunakan aksesoris! Kamu belum memiliki item ini.");
        } else {
            $user->unequipAccessory($request->code);
            return redirect()->back()->with('success', "Aksesoris berhasil dilepas!");
        }
    }

    /**
     * Rename the mascot.
     */
    public function rename(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|min:1|max:20',
        ]);

        $user = $request->user();
        $user->mascot_name = $request->name;
        $user->save();

        return redirect()->back()->with('success', "Nama maskot berhasil diubah menjadi: {$request->name}!");
    }
}
