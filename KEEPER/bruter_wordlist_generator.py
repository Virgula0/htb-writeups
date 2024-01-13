import string
import itertools

replace_char = "●"
substitution = string.printable

print("Substitutions on " + substitution)
replacements_list = []


def generate_permutations_with_replacement(charset, string_with_placeholders):
    # Find the positions of '$' in the string
    placeholder_positions = [i for i, char in enumerate(string_with_placeholders) if char == replace_char]

    # Generate all combinations of characters from the charset at placeholder positions
    replacements = itertools.product(charset, repeat=len(placeholder_positions))

    # Initialize an empty list to store the results
    global replacements_list

    # Replace placeholders with combinations and append the results to the list
    for replacement_combination in replacements:
        replaced_string = list(string_with_placeholders)
        for position, replacement_char in zip(placeholder_positions, replacement_combination):
            replaced_string[position] = replacement_char
        replacements_list.append(''.join(replaced_string))


with open("inputs.txt","r") as file:
    print("Generating substitutions")
    for i, psw in enumerate(file, start=1):
        generate_permutations_with_replacement(substitution, psw)    
    print(len(replacements_list))

print("Saving wordlist to a file...")

with open("wordlist.txt","w") as file:
    for x in replacements_list:
        file.write(str(x) +'\n')