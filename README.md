# Indra

## Introduction

This is a proof-of-concept library for the following idea:

> Is it possible to do version control in a database, just as you can with source code?

The answer, it appears, is: Yes, you can!

This library, written in PHP 5.6, and using an existing relational database (e.g. MySql), has these features:

 * It generates database tables and PHP classes for types, attributes, and models
 * Each change to the database is done in a commit, in a branch.
 * The default branch is master. Branches can be created starting from the last commit of an existing branch. 
 * A branch may be merged into another branch.
 * An application can check out a previous commit of the data and view that data just like it was the last version of the data.

## Applications

Note! This library itself is just meant as a proof-of-concept. It works, but is not tested enough to be used in a production environment.
 
A more mature version of this system could be used for:
 
 * Auditing: keep track of the Who, What, When and Why of changes in the data
 * Recovering: undo unintended changes in the data
 * Debugging: to debug a problem it can be handy to use your application with the data as it was at some specific time in the past
 * Fiating: data branching allows your user to create changes in a non-live branch and merge it to the live branch after a review  
 